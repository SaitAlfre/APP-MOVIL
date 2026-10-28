[graphify] MultiDiGraph edge-collapse diagnostic
input: <in-memory>
input_stage: provided JSON (normal graph.json is post-build)
effective_directed: <direct-call>
nodes: 456
unverified_code_nodes: 0
raw_edges: 2637
valid_candidate_edges: 2497
missing_endpoint_edges: 0
dangling_endpoint_edges: 140
external_reference_edges: 0
self_loop_edges: 1
exact_duplicate_edges: 231
directed_unique_endpoint_pairs: 1534
directed_same_endpoint_collapsed_edges: 963
undirected_unique_endpoint_pairs: 1369
undirected_same_endpoint_collapsed_edges: 1128
same_endpoint_group_count: 110
relation_variant_groups: 0
source_file_variant_groups: 0
source_location_variant_groups: 106
context_variant_groups: 0
post_build_graph_type: Graph
post_build_edges: 1508
producer_suppression_sites: 12
producer_suppression_examples:
  - L1815 seen_ids arity=unknown
  - L2379 seen_ids arity=unknown
  - L2381 seen_doc_refs arity=unknown
  - L3048 seen_ids arity=unknown
  - L3195 seen_ids arity=unknown
  - L4336 seen_keys arity=unknown
  - L4522 seen_keys arity=unknown
  - L7933 seen_ids arity=unknown
examples:
  - docs_scrum_product_backlog_source_document -> docs_scrum_tareas_source_document edges=106 relations=['references'] locations=['L1019', 'L1059', 'L1060', 'L1099', 'L1100', 'L1139', 'L1184', 'L1215', 'L1226', 'L1266', 'L1311', 'L133', 'L134', 'L1351', 'L1391', 'L1431', 'L1471', 'L1511', 'L1551', 'L1591', 'L1631', 'L1671', 'L1711', 'L1751', 'L1787', 'L1794', 'L181', 'L182', 'L224', 'L269', 'L270', 'L312', 'L357', 'L358', 'L388', 'L399', 'L439', 'L479', 'L519', 'L520', 'L564', 'L565', 'L604', 'L644', 'L645', 'L675', 'L686', 'L726', 'L766', 'L767', 'L806', 'L807', 'L846', 'L847', 'L894', 'L895', 'L9', 'L93', 'L934', 'L94', 'L974'] contexts=['']
  - docs_requisitos_rf_fuente_y_cobertura_source_document -> docs_scrum_product_backlog_source_document edges=103 relations=['references'] locations=['L12', 'L16', 'L17', 'L18', 'L19', 'L20', 'L21', 'L22', 'L23', 'L24', 'L25', 'L26', 'L27', 'L28', 'L29', 'L30', 'L31', 'L32', 'L33', 'L34', 'L35', 'L36', 'L37', 'L38', 'L39', 'L40', 'L41', 'L42', 'L43', 'L44', 'L45', 'L46', 'L47', 'L48', 'L49', 'L50', 'L51', 'L52', 'L53', 'L54', 'L55', 'L56', 'L57', 'L58', 'L59', 'L60', 'L61', 'L62', 'L63', 'L64', 'L65', 'L66', 'L67', 'L68', 'L69', 'L70', 'L71', 'L72', 'L73', 'L74', 'L75', 'L76'] contexts=['']
  - docs_scrum_tareas_source_document -> docs_scrum_product_backlog_source_document edges=87 relations=['references'] locations=['L106', 'L107', 'L108', 'L109', 'L110', 'L111', 'L112', 'L113', 'L114', 'L18', 'L19', 'L20', 'L21', 'L22', 'L23', 'L24', 'L25', 'L26', 'L27', 'L28', 'L29', 'L3', 'L30', 'L31', 'L32', 'L33', 'L34', 'L35', 'L36', 'L42', 'L43', 'L44', 'L45', 'L46', 'L47', 'L48', 'L49', 'L50', 'L51', 'L52', 'L53', 'L54', 'L55', 'L56', 'L57', 'L58', 'L59', 'L60', 'L61', 'L62', 'L63', 'L64', 'L65', 'L66', 'L67', 'L68', 'L69', 'L70', 'L71', 'L72', 'L73', 'L74', 'L75', 'L76', 'L77', 'L78', 'L79', 'L80', 'L81', 'L82', 'L83', 'L84', 'L85', 'L86', 'L87', 'L88', 'L89', 'L90', 'L91', 'L92', 'L93', 'L94', 'L95', 'L96', 'L97', 'L98', 'L99'] contexts=['']
  - docs_scrum_trazabilidad_source_document -> docs_scrum_tareas_source_document edges=80 relations=['references'] locations=['L10', 'L11', 'L12', 'L13', 'L14', 'L15', 'L16', 'L17', 'L18', 'L19', 'L20', 'L21', 'L22', 'L23', 'L24', 'L25', 'L26', 'L27', 'L28', 'L29', 'L30', 'L31', 'L32', 'L33', 'L34', 'L35', 'L36', 'L37', 'L38', 'L39', 'L40', 'L41', 'L42', 'L43', 'L44', 'L45', 'L46', 'L47', 'L48', 'L49', 'L58', 'L9'] contexts=['']
  - docs_requisitos_rf_fuente_y_cobertura_document -> docs_scrum_product_backlog_document edges=62 relations=['references'] locations=['L12', 'L16', 'L17', 'L18', 'L19', 'L20', 'L21', 'L22', 'L23', 'L24', 'L25', 'L26', 'L27', 'L28', 'L29', 'L30', 'L31', 'L32', 'L33', 'L34', 'L35', 'L36', 'L37', 'L38', 'L39', 'L40', 'L41', 'L42', 'L43', 'L44', 'L45', 'L46', 'L47', 'L48', 'L49', 'L50', 'L51', 'L52', 'L53', 'L54', 'L55', 'L56', 'L57', 'L58', 'L59', 'L60', 'L61', 'L62', 'L63', 'L64', 'L65', 'L66', 'L67', 'L68', 'L69', 'L70', 'L71', 'L72', 'L73', 'L74', 'L75', 'L76'] contexts=['']
note: normal graph.json is post-build; raw producer loss must be measured earlier.