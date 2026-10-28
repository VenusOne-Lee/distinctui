import DistinctUI
import DistinctUIExamples
import SwiftUI

/// Runs the example screens. Open on one with a launch argument: -screen profile
@main
struct DemoApp: App {
    init() {
        DUIAppearance.apply()
    }

    private var screen: ExamplesHome.Screen {
        UserDefaults.standard.string(forKey: "screen").flatMap(ExamplesHome.Screen.init(rawValue:)) ?? .makers
    }

    var body: some Scene {
        WindowGroup {
            ExamplesHome(screen: screen)
                .distinctUI(palette: .signal)
        }
    }
}
