#!/usr/bin/env python3
"""Comprobaciones documentales locales. No modifica Git ni contacta servicios."""
from __future__ import annotations
import argparse,csv,hashlib,html,json,re,subprocess,sys
from datetime import date
from pathlib import Path
from urllib.parse import unquote, urlsplit

ROOT=Path(__file__).resolve().parents[2]
DOCS=ROOT/"docs"
ALLOWED={"Cumple","Parcialmente cumple","No cumple","Pendiente de validación","Ejercicio simulado / representación académica"}
FROZEN={"docs/metodologia/APLICACION_METODOLOGIA_SCRUM.md","docs/metodologia/RECONSTRUCCION_RETROSPECTIVA_ECOLACTEA_DIGITAL.md"}
REQUIRED=[
"README.md","docs/README.md","docs/COMO-TRABAJAMOS.md","docs/guia-mantenimiento-documentacion.md",
"docs/proceso-scrum-del-proyecto.md","docs/scrum/product-backlog.md","docs/scrum/tareas.md",
"docs/scrum/calendario-y-capacidad.md","docs/scrum/definition-of-done.md",
"docs/scrum/eventos-y-registros.md","docs/kanban/tablero.md",
"docs/plantillas/historia-usuario.md","docs/plantillas/tarea.md","docs/plantillas/acta-evento-scrum.md",
"docs/plantillas/caso-de-prueba.md","docs/plantillas/ejecucion-prueba.md","docs/plantillas/reporte-defecto.md",
"docs/pruebas/README.md",".github/PULL_REQUEST_TEMPLATE.md"]
def read(path):return (ROOT/path).read_text(encoding="utf-8")
def publication_files():
    # El manifiesto incluye rutas nuevas y bajas por traslado; no es un staging automático.
    extensions={".md",".json",".csv",".log",".svg",".png",".jpg",".py",".kt",".php",".kts",".yml",".yaml",".js",".mjs",".rules",".xml",".txt"}
    paths={p for p in DOCS.rglob("*") if p.is_file() and (p.suffix in extensions or p.name==".gitignore")}
    paths.add(ROOT/"README.md")
    paths.update(p for p in (ROOT/".github").rglob("*") if p.is_file() and p.suffix in {".md",".yml",".yaml"})
    changed=subprocess.check_output(["git","diff","HEAD","--no-renames","--name-only","-z"],cwd=ROOT).decode().split("\0")
    changed += subprocess.check_output(["git","ls-files","--others","--exclude-standard","-z"],cwd=ROOT).decode().split("\0")
    for name in changed:
        if name and Path(name).suffix in extensions:paths.add(ROOT/name)
    safe=[]
    for p in sorted(paths):
        rel=p.relative_to(ROOT)
        if p.is_symlink() or p.name.startswith(".env") or p.name=="google-services.json" or any(x in rel.parts for x in {"node_modules","vendor","build","__pycache__"}):continue
        if subprocess.run(["git","check-ignore","-q","--",str(rel)],cwd=ROOT).returncode==0:continue
        safe.append(p)
    return safe
def artifact_matches(folder,name,expected):
    path=(folder/name).resolve()
    return path.parent==folder.resolve() and path.is_file() and hashlib.sha256(path.read_bytes()).hexdigest()==expected
def source_version(name,expected):
    """La evidencia histórica conserva su versión; no acredita el código que cambió después."""
    path=(ROOT/name).resolve()
    if not path.is_relative_to(ROOT) or not re.fullmatch(r"[0-9a-f]{64}",expected):return None
    if path.is_file() and hashlib.sha256(path.read_bytes()).hexdigest()==expected:return "working-tree"
    history=subprocess.run(["git","log","--all","--format=%H","--",name],cwd=ROOT,capture_output=True,text=True)
    if history.returncode:return None
    for commit in history.stdout.splitlines():
        blob=subprocess.run(["git","show",commit+":"+name],cwd=ROOT,capture_output=True)
        if blob.returncode==0 and hashlib.sha256(blob.stdout).hexdigest()==expected:return "git:"+commit
    return None
def anchors(text):
    found=set(re.findall(r'<a\s+id=["\']([^"\']+)',text));counts={};fence=None
    for line in text.splitlines():
        if line.startswith((chr(96)*3,"~~~")):fence=None if fence else line[:3];continue
        if fence:continue
        m=re.match(r"^#{1,6}\s+(.+)",line)
        if not m:continue
        title=re.sub(r"\[([^]]+)\]\([^)]+\)",r"\1",m[1])
        title=re.sub(r"<[^>]+>","",html.unescape(title)).lower()
        slug=re.sub(r"\s","-","".join(c for c in title if c.isalnum() or c in "_- \t").strip())
        n=counts.get(slug,0);counts[slug]=n+1;found.add(slug+(f"-{n}" if n else ""))
    return found
def rows(text):return [[x.strip() for x in re.split(r"(?<!\\)\|",s)[1:-1]] for s in text.splitlines() if s.startswith("|") and s.endswith("|")]
def parse_catalog():
    text=read("docs/scrum/product-backlog.md")
    starts=list(re.finditer(r"^### (HU-\d{2}(?:-[A-Z])?) — (.+)$",text,re.M));stories={}
    for i,m in enumerate(starts):
        block=text[m.start():starts[i+1].start() if i+1<len(starts) else len(text)]
        # Parent/enabler sections use level 2 and cannot become executable stories.
        block=block.split('\n## HU-14')[0]
        priority=re.search(r"\*\*MoSCoW / tamaño inicial:\*\* (\S+) / (\d+) SP",block)
        stories[m[1]]={"id":m[1],"title":m[2],"priority":priority[1] if priority else None,
            "points":int(priority[2]) if priority else None,
            "criteria":list(dict.fromkeys(re.findall(r"\*\*(CA-HU-[A-Z0-9-]+)\*\*",block))),
            "scenarios":block.count("Escenario:"),
            "dependencies":re.findall(r"\[([A-Z]+-\d{2}(?:-[A-Z])?)\]\(product-backlog.md#",re.search(r"- \*\*Dependencias:\*\* (.+)",block)[1]) if re.search(r"- \*\*Dependencias:\*\* (.+)",block) else [],
            "source":block}
    tasks={}
    for c in rows(read("docs/scrum/tareas.md")):
        if not c or not re.search(r'id="t-\d+"',c[0]):continue
        id=re.search(r"\*\*(T-\d+)\*\*",c[0])[1]
        if id in tasks:raise ValueError(f"Tarea duplicada: {id}")
        related=re.search(r"\[(HU-\d{2}(?:-[A-Z])?|RNF-\d{2})\]",c[1])
        tasks[id]={"id":id,"title":c[0].split(": ",1)[-1],"related":related[1] if related else None,
          "owner":c[2],"historical":"Ventana" in c[3] or bool(re.fullmatch(r"S\d{2}",c[3])),
          "hours":c[3],"selection":c[4],"criteria":re.findall(r"CA-HU-[A-Z0-9-]+",c[1])}
    # T-01…19 are historical references, not new operational cards.
    for id,t in tasks.items():t["historical"]=int(id[2:])<=19
    return stories,tasks
def export_kanban(stories,tasks):
    target=DOCS/"kanban/importacion-propuesta.csv"
    header=["ID","Tipo","Titulo","HU_RNF","Responsable_propuesto","Prioridad_propuesta","SP_hipoteticos","Horas_propuestas","Sprint_candidato","Estado_inicial_propuesto","URL_fuente"]
    data=[]
    for id,s in stories.items():
        selection=re.search(r"\*\*Selección:\*\* ([^;]+)",s["source"])
        data.append([id,"Historia",s["title"],id,"",s["priority"],s["points"],"",selection[1] if selection else "Sin selección","Backlog",f"docs/scrum/product-backlog.md#{id.lower()}"])
    for id,t in tasks.items():
        if t["historical"]:continue
        h=stories.get(t["related"],{})
        data.append([id,"Tarea",t["title"],t["related"],t["owner"],h.get("priority","Por ratificar"),"",t["hours"],t["selection"].split(";")[0],"Backlog",f"docs/scrum/tareas.md#{id.lower()}"])
    with target.open("w",newline="",encoding="utf-8") as f:
        w=csv.writer(f,lineterminator="\n");w.writerow(header);w.writerows(data)
    config={"schema":1,"nature":"Propuesta reproducible; no es un Project creado ni historial de movimientos",
      "repository":"SaitAlfre/APP-MOVIL","columns":["Backlog","To Do","In Progress","Review","Done"],
      "wip":{"In Progress":2,"Review":2,"total_active":2},"wip_unit":"Tarea, sin duplicar HU padre",
      "fields":["Tipo","ID","HU_RNF","Responsable propuesto","Prioridad","Sprint candidato","SP hipotéticos","Horas propuestas","Bloqueo","PR","Evidencia","Inicio real","Fin real"],
      "cards":len(data),"stories":len(stories),"actionable_tasks":sum(not t["historical"] for t in tasks.values()),
      "source":"product-backlog.md + tareas.md; CSV derivado, no editar estados aquí",
      "credentials":"No incluidas. Crear recursos requiere sesión/autorización y comprobar que no existen.",
      "csv":"importacion-propuesta.csv"}
    (DOCS/"kanban/configuracion-propuesta.json").write_text(json.dumps(config,ensure_ascii=False,indent=2)+"\n")
    return config
def http_url_error(url):
    parsed=urlsplit(url)
    if parsed.scheme not in {"http","https"}:return None
    if any(c.isspace() for c in url):return "URL HTTP con espacios sin codificar"
    if not parsed.netloc:return "URL HTTP sin servidor"
    if parsed.netloc=="github.com" and parsed.path.startswith("/SaitAlfre/APP-MOVIL/commit/") and not re.fullmatch(r"/SaitAlfre/APP-MOVIL/commit/[a-f0-9]{40}/?",parsed.path):
        return "URL de commit sin SHA completo válido"
    return None
def verify(sources=None):
    errors=[];warnings=[];local_links=0;commit_hashes=set();historical_hashes=set()
    paths=sorted(ROOT.glob("*.md"))+sorted(DOCS.rglob("*.md"))+[ROOT/".github/PULL_REQUEST_TEMPLATE.md"]
    cache={p:anchors(p.read_text()) for p in paths if p.exists()}
    for r in REQUIRED:
        if not (ROOT/r).is_file():errors.append("Falta archivo "+r)
    for p in paths:
        if not p.exists():continue
        rel=str(p.relative_to(ROOT));text=p.read_text()
        for m in re.finditer(r"!?\[[^\]\n]*\]\(([^)\n]+)\)",text):
            url=m[1].strip().split(' "')[0].strip("<>")
            problem=http_url_error(url)
            if problem:(warnings if rel in FROZEN else errors).append(rel+": "+problem+" "+url)
            if re.match(r"^[A-Za-z][A-Za-z0-9+.-]*:",url):
                cm=re.search(r"github\.com/SaitAlfre/APP-MOVIL/commit/([a-f0-9]{40})",url)
                if cm:
                    commit_hashes.add(cm[1])
                    if rel=="docs/scrum/evidencias-commits.md":historical_hashes.add(cm[1])
                continue
            path,_,frag=url.partition("#");path=unquote(path);frag=unquote(frag);dest=(p.parent/path).resolve() if path else p;local_links+=1
            if not dest.exists():errors.append(f"{rel}: destino ausente {url}")
            elif frag and dest.suffix==".md" and frag not in cache.get(dest,anchors(dest.read_text())):errors.append(f"{rel}: ancla ausente {url}")
        if re.search(r"(?m)^\s*python 3(?:\s|$)",text) or "`python 3 " in text:
            (warnings if rel in FROZEN else errors).append(rel+": comando python3 fragmentado")
        expected=None;fence=False
        for n,line in enumerate(text.splitlines(),1):
            if line.startswith((chr(96)*3,"~~~")):fence=not fence;expected=None;continue
            if fence:continue
            if line.startswith("|") and line.endswith("|"):
                count=len(re.split(r"(?<!\\)\|",line))-2
                if expected is not None and count!=expected:
                    (warnings if rel in FROZEN else errors).append(f"{rel}:{n}: columnas {count}, se esperaban {expected}")
                expected=count
            else:expected=None
    stories,tasks=parse_catalog();criteria=[c for s in stories.values() for c in s["criteria"]]
    if len(stories)<10:errors.append("Menos de diez HU funcionales")
    if len(criteria)!=len(set(criteria)):errors.append("Criterios duplicados en catálogo")
    for id,s in stories.items():
        if s["scenarios"]<2:errors.append(f"{id}: faltan dos escenarios")
        if s["priority"] not in {"Must","Should","Could","Won’t"}:errors.append(f"{id}: prioridad inválida")
        if s["points"] not in {1,2,3,5,8,13}:errors.append(f"{id}: estimación fuera de escala o corte >13")
        if not any(t["related"]==id for t in tasks.values()):errors.append(f"{id}: sin tarea")
        for dep in s["dependencies"]:
            if dep not in stories:errors.append(f"{id}: dependencia ausente {dep}")
    seen=set();active=set()
    def visit(id):
        if id in active:errors.append("Ciclo de dependencias "+id);return
        if id in seen:return
        active.add(id)
        for dep in stories[id]["dependencies"]:
            if dep in stories:visit(dep)
        active.remove(id);seen.add(id)
    for id in stories:visit(id)
    for id,t in tasks.items():
        for ca in t["criteria"]:
            if ca not in criteria:errors.append(f"{id}: CA inexistente {ca}")
        if t["related"] not in stories and t["related"] not in {"HU-14","HU-15"} and not str(t["related"]).startswith("RNF-"):errors.append(f"{id}: relación inexistente")
    rfrows=[c for c in rows(read("docs/requisitos/rf-fuente-y-cobertura.md")) if c and re.fullmatch(r"RF-[A-Z]+-\d{2}",c[0])]
    if len(rfrows)!=61 or len({c[0] for c in rfrows})!=61:errors.append("RF originales no suman 61 únicos")
    for c in rfrows:
        if not any(rf==c[0] for s in stories.values() for rf in re.findall(r"RF-[A-Z]+-\d{2}",s["source"])):errors.append(c[0]+": sin historia")
        if "CA-HU-" not in c[3]:errors.append(c[0]+": sin criterio específico")
    rnfs=re.findall(r'id="(rnf-\d{2})"',read("docs/requisitos/rnf-fuente-y-cobertura.md"))
    if len(rnfs)!=17 or len(set(rnfs))!=17:errors.append("RNF no suman 17 únicos")
    for h in commit_hashes:
        if subprocess.run(["git","cat-file","-e",h+"^{commit}"],cwd=ROOT,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode:errors.append("Commit no localizable "+h)
    if len(historical_hashes)!=22:errors.append(f"Selección Git histórica: {len(historical_hashes)} hashes, se esperaban 22")
    matrix=[c for c in rows(read("docs/academico/matriz-cumplimiento.md")) if c and re.match(r"^\d{2} —",c[0])]
    if len(matrix)!=36:errors.append("Matriz principal no conserva 36 filas")
    for row in matrix:
        if any(x not in ALLOWED for x in row[2:5]):errors.append("Estado inválido de matriz "+row[0])
    ep=re.findall(r"^\| (EP-\d{2}) \|",read("docs/academico/evidencias-pendientes.md"),re.M)
    if len(ep)!=15 or len(set(ep))!=15:errors.append("EP no conserva 15 IDs únicos")
    for p in paths:
        if not p.exists():continue
        ids=re.findall(r'<a\s+id=["\']([^"\']+)',p.read_text())
        if len(ids)!=len(set(ids)):errors.append(str(p.relative_to(ROOT))+": anclas explícitas duplicadas")
    starts=[]
    for n in range(1,11):
        p=DOCS/f"scrum/sprint-{n}.md"
        if not p.exists():errors.append(f"Sprint {n}: falta archivo");continue
        m=re.search(r"(2026-\d{2}-\d{2}) → (2026-\d{2}-\d{2})",p.read_text().splitlines()[0])
        if not m:errors.append(f"Sprint {n}: fechas de cabecera inválidas");continue
        a,b=map(date.fromisoformat,m.groups());starts.append((a,b))
        if a.weekday()!=3 or (b-a).days!=7:errors.append(f"Sprint {n}: no es jueves→jueves de 7 días")
    for prev,now in zip(starts,starts[1:]):
        if prev[1]!=now[0]:errors.append("Calendario de sprints discontinuo")
    template_fields={"historia-usuario.md":["INVEST","Como","Gherkin"],
      "tarea.md":["Responsable / revisor","Estado","Evidencia","Bloqueo"],
      "acta-evento-scrum.md":["Participantes","Fecha","Acciones"],
      "caso-de-prueba.md":["Esperado","Estado","Precondiciones"],
      "ejecucion-prueba.md":["Obtenido","Hash","Revisor","Estado"],
      "reporte-defecto.md":["Severidad","Prioridad","Regresión"]}
    for name,fields in template_fields.items():
        value=read("docs/plantillas/"+name).lower()
        for field in fields:
            if field.lower() not in value:errors.append(name+": falta campo "+field)
    # No public secrets; historical ZIPs are deliberately outside the publication list.
    patterns={
      "ruta privada":re.compile(r"(?<![\w])/"+"home"+"/"),
      "clave privada":re.compile(r"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
      "token GitHub":re.compile(r"\b(?:ghp_[A-Za-z0-9]{25,}|github_pat_[A-Za-z0-9_]{25,})"),
      "correo":re.compile(r"\b[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}\b")}
    patterns["secreto asignado"]=re.compile(r"(?i)(?:password|api[_-]?key|secret|access[_-]?token)\s*[:=]\s*["+chr(34)+chr(39)+r"]([A-Za-z0-9/+_=.-]{12,})["+chr(34)+chr(39)+r"]")
    for p in [*DOCS.rglob("*"),*ROOT.glob("*.md")]:
        if not p.is_file() or p.suffix not in {".md",".json",".csv",".log",".svg",".py"}:continue
        for name,rgx in patterns.items():
            if rgx.search(p.read_text(errors="replace")):errors.append(str(p.relative_to(ROOT))+": patrón "+name)
    source_checks=[]
    if sources:
        root=Path(sources)
        for c in rows(read("docs/academico/fuentes-docentes.md")):
            if not c or not re.fullmatch(r"F[1-6]",c[0]):continue
            match=re.search(r"\b[a-f0-9]{64}\b",c[2])
            if not match:continue
            files=list(root.rglob(c[1]))
            ok=len(files)==1 and hashlib.sha256(files[0].read_bytes()).hexdigest()==match[0]
            source_checks.append({"id":c[0],"file":c[1],"hash_matches":ok})
            if not ok:errors.append("Fuente no coincide o no localizable: "+c[0])
    config=DOCS/"kanban/configuracion-propuesta.json"
    if config.exists():
        c=json.loads(config.read_text())
        if c["columns"]!=["Backlog","To Do","In Progress","Review","Done"]:errors.append("Columnas Kanban incorrectas")
        if c["wip"]!={"In Progress":2,"Review":2,"total_active":2}:errors.append("WIP no coincide con la política")
        cards=list(csv.DictReader((DOCS/"kanban/importacion-propuesta.csv").open(encoding="utf-8")))
        expected=set(stories)|{id for id,t in tasks.items() if not t["historical"]}
        if len(cards)!=len(expected) or {r["ID"] for r in cards}!=expected or c["cards"]!=len(cards):errors.append("Export Kanban divergente o duplicado: regenerar")
        for row in cards:
            if row["Estado_inicial_propuesto"]!="Backlog":errors.append("CSV mezcla estado operativo con propuesta")
            if row["Tipo"]=="Historia" and row["Horas_propuestas"]:errors.append("HU con horas de tarea en CSV")
            if row["Tipo"]=="Tarea" and row["SP_hipoteticos"]:errors.append("Tarea con SP de historia en CSV")
    report=DOCS/"pruebas/ejecuciones/resultados-2026-10-22.json"
    if report.exists():
        data=json.loads(report.read_text());executions=data["runs"]
        if len({r["id"] for r in executions})!=len(executions):errors.append("Ejecuciones duplicadas")
        for run in executions:
            if run["state"] not in {"Passed","Failed","Blocked","Not Run"}:errors.append("Estado EXE inválido")
            if run.get("log"):
                log=report.parent/run["log"]
                if not log.exists() or hashlib.sha256(log.read_bytes()).hexdigest()!=run["published_log_sha256"]:errors.append(run["id"]+": log alterado/ausente")
            for name,sha in run.get("screenshots_sha256",{}).items():
                p=DOCS/"pruebas/evidencias"/name
                if not p.exists() or hashlib.sha256(p.read_bytes()).hexdigest()!=sha:errors.append(run["id"]+": captura alterada/ausente")
        for name,suite in data["final_suites"].items():
            if suite["total"]!=suite["passed"]+suite["failed"]+suite["skipped"]:errors.append(name+": conteo de resultados inconsistente")
            if "tests" in suite and len(suite["tests"])!=suite["total"]:errors.append(name+": detalle de tests incompleto")
        for name,sha in data.get("working_tree_changes",data["working_tree_test_changes"]).items():
            version=source_version(name,sha)
            if version is None:errors.append(name+": versión ejecutada no conservada o hash alterado")
            elif version!="working-tree":warnings.append(name+": evidencia histórica verificada en "+version+"; no acredita la versión actual")
        for name,sha in data.get("additional_evidence_sha256",{}).items():
            p=DOCS/"pruebas/evidencias"/name
            if not p.exists() or hashlib.sha256(p.read_bytes()).hexdigest()!=sha:errors.append(name+": evidencia adicional alterada/ausente")
    # Recibos nuevos se validan sin sobrescribir las 26 ejecuciones originales.
    for receipt in (DOCS/"pruebas/ejecuciones/automaticas").glob("*/resultado.json"):
        try:
            data=json.loads(receipt.read_text())
            if data["state"] not in {"Passed","Failed","Blocked","Not Run"}:errors.append(receipt.parent.name+": estado inválido")
            log=(receipt.parent/data["log"]).resolve()
            if log.parent!=receipt.parent.resolve() or not log.is_file() or hashlib.sha256(log.read_bytes()).hexdigest()!=data["published_log_sha256"]:errors.append(receipt.parent.name+": log alterado o fuera de carpeta")
            artifacts=dict(data.get("screenshots",{}))
            if data.get("screenshot_sha256"):artifacts["arranque.png"]=data["screenshot_sha256"]
            if data.get("ui_xml_sha256"):artifacts["ui.xml"]=data["ui_xml_sha256"]
            for name,expected_hash in artifacts.items():
                if not artifact_matches(receipt.parent,name,expected_hash):errors.append(receipt.parent.name+": artefacto alterado/ausente/fuera de carpeta "+name)
            tests=data.get("tests")
            if tests:
                if tests["total"]!=sum(tests[k] for k in ["passed","failed","skipped"]):errors.append(receipt.parent.name+": totales incoherentes")
                if data["state"]=="Passed" and (tests["failed"] or tests["skipped"]):errors.append(receipt.parent.name+": Passed con fallos u omisiones")
                if "cases" in tests:
                    cases=tests["cases"]
                    missing=tests["total"]-len(cases)
                    if missing<0 or sum(c["state"]=="Passed" for c in cases)!=tests["passed"] or sum(c["state"]=="Failed" for c in cases)!=tests["failed"] or sum(c["state"]=="Skipped" for c in cases)+missing!=tests["skipped"]:
                        errors.append(receipt.parent.name+": detalle de casos incoherente")
                    elif missing:warnings.append(receipt.parent.name+": "+str(missing)+" controles no alcanzados por el arnés; sin nombres en el recibo inicial")
        except (ValueError,KeyError,OSError) as e:errors.append(receipt.parent.name+": recibo incompleto "+str(e))
    return {"ok":not errors,"scope":"Estructura y evidencia referenciada; no sustituye revisión humana o validación del beneficiario",
      "counts":{"markdown":len(paths),"local_links":local_links,"stories":len(stories),"criteria":len(criteria),"scenarios":sum(s["scenarios"] for s in stories.values()),"tasks":len(tasks),"RF":len(rfrows),"RNF":len(rnfs),"commits":len(historical_hashes),"linked_commits":len(commit_hashes),"matrix_rows":len(matrix),"EP":len(ep)},
      "errors":errors,"warnings":warnings,"sources":source_checks}
def main():
    ap=argparse.ArgumentParser(description=__doc__);ap.add_argument("--json",type=Path);ap.add_argument("--fuentes-dir");ap.add_argument("--export-kanban",action="store_true");ap.add_argument("--lista-publicable",action="store_true")
    args=ap.parse_args()
    if args.lista_publicable:
        print("\n".join(str(p.relative_to(ROOT)) for p in publication_files()));return 0
    if args.export_kanban:export_kanban(*parse_catalog())
    result=verify(args.fuentes_dir)
    output=json.dumps(result,ensure_ascii=False,indent=2)
    if args.json:args.json.write_text(output+"\n",encoding="utf-8")
    print(output);return 0 if result["ok"] else 1
if __name__=="__main__":raise SystemExit(main())
