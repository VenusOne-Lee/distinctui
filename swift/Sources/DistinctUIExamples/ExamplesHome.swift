import DistinctUI
import SwiftUI

/// The example screens, in a tab bar like an app would use them.
public struct ExamplesHome: View {
    public enum Screen: String, CaseIterable, Sendable {
        case makers, profile, share, signin, settings, components
    }

    @State private var tab: Screen

    /// - Parameter screen: the tab to open on. Sign-in opens on its own, without the tab bar.
    public init(screen: Screen = .makers) {
        _tab = State(initialValue: screen)
    }

    public var body: some View {
        if tab == .signin {
            SignInExample()
        } else {
            TabView(selection: $tab) {
                NavigationStack { MakersExample() }
                    .tabItem { Label("Makers", systemImage: "circle.dashed") }
                    .tag(Screen.makers)
                NavigationStack { FormExample() }
                    .tabItem { Label("Share", systemImage: "plus.circle") }
                    .tag(Screen.share)
                NavigationStack { ProfileExample() }
                    .tabItem { Label("You", systemImage: "person.crop.circle") }
                    .tag(Screen.profile)
                NavigationStack { SettingsExample() }
                    .tabItem { Label("Settings", systemImage: "gearshape") }
                    .tag(Screen.settings)
                NavigationStack { ComponentsExample() }
                    .tabItem { Label("Kit", systemImage: "square.grid.2x2") }
                    .tag(Screen.components)
            }
        }
    }
}

#Preview {
    ExamplesHome().distinctUI()
}
