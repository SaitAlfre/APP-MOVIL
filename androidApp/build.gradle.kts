import org.jetbrains.kotlin.gradle.dsl.JvmTarget
import java.util.Properties

plugins {
    alias(libs.plugins.androidApplication)
    alias(libs.plugins.composeCompiler)
    alias(libs.plugins.googleServices) apply false
}

// Vista local explícita para probar el portal cuando no se ha entregado google-services.json.
// La compilación normal conserva Firebase y falla si su configuración no está disponible.
val localPreview = providers.gradleProperty("localPreview").orNull == "true"
if (!localPreview) apply(plugin = "com.google.gms.google-services")

// URL del panel web (Laravel) al que se envían las entregas y del que se leen las liquidaciones. No es un
// secreto: cada usuario se autentica con su propio usuario y PIN. Se toma de `-Pecolecta.servidorUrl=...`
// o de `ecolecta.servidorUrl=...` en local.properties (p. ej. http://10.0.2.2:8000 para el emulador).
// Las APK normales apuntan al panel publicado. Las pruebas pueden cambiar la URL explícitamente.
val servidorUrl: String = providers.gradleProperty("ecolecta.servidorUrl").orNull
    ?: rootProject.file("local.properties").takeIf { it.exists() }?.let { archivo ->
        Properties().apply { archivo.inputStream().use { load(it) } }.getProperty("ecolecta.servidorUrl")
    }
    ?: "https://ecolecta-5cfec46d4968.herokuapp.com"

kotlin {
    compilerOptions {
        jvmTarget = JvmTarget.JVM_17
    }
}
dependencies {
    implementation(project(":shared"))

    implementation(libs.androidx.activity.compose)
    implementation(libs.koin.core)
    implementation(libs.koin.android)
    implementation(libs.qr.kit)

    implementation(libs.compose.uiToolingPreview)
    debugImplementation(libs.compose.uiTooling)
}

android {
    namespace = "pe.ecolecta"
    compileSdk = libs.versions.android.compileSdk.get().toInt()

    defaultConfig {
        applicationId = "pe.ecolecta"
        minSdk = libs.versions.android.minSdk.get().toInt()
        targetSdk = libs.versions.android.targetSdk.get().toInt()
        versionCode = 1
        versionName = "1.0"
        buildConfigField("boolean", "LOCAL_PREVIEW", localPreview.toString())
        buildConfigField("String", "SERVIDOR_URL", "\"${servidorUrl.trim()}\"")
    }
    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
    }
    buildTypes {
        debug {
            if (localPreview) {
                applicationIdSuffix = ".preview"
                versionNameSuffix = "-local"
            }
        }
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
    buildFeatures {
        compose = true
        buildConfig = true
    }
}

tasks.matching { it.name.contains("Release", ignoreCase = true) }.configureEach {
    if (localPreview) doFirst { error("localPreview solo permite builds de prueba Debug.") }
}
