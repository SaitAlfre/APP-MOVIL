import SwiftUI
import Shared

@main
struct iOSApp: App {
    init() {
        KoinIOSKt.iniciarKoinIOS()
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
        }
    }
}