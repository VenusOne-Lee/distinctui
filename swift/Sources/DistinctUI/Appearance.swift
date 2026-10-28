import SwiftUI
import UIKit

/// Sets Bricolage and black on the system bars, which SwiftUI can't style on its own.
/// Call once at launch, before the first screen appears. It's optional: without it the bars
/// keep the system font, which is fine, but titles look more like the rest of the app with it.
public enum DUIAppearance {
    @MainActor
    public static func apply() {
        DUIFont.register()

        let bar = UINavigationBarAppearance()
        bar.configureWithOpaqueBackground()
        bar.backgroundColor = .white
        bar.shadowColor = UIColor(white: 0.898, alpha: 1) // grey-200 hairline
        if let title = DUIFont.uiFont(17, weight: .semibold) {
            bar.titleTextAttributes = [.font: UIFontMetrics(forTextStyle: .headline).scaledFont(for: title), .foregroundColor: UIColor.black]
        }
        if let large = DUIFont.uiFont(34, weight: .bold) {
            bar.largeTitleTextAttributes = [
                .font: UIFontMetrics(forTextStyle: .largeTitle).scaledFont(for: large),
                .foregroundColor: UIColor.black,
                .kern: -1.2,
            ]
        }
        UINavigationBar.appearance().standardAppearance = bar
        UINavigationBar.appearance().compactAppearance = bar
        UINavigationBar.appearance().scrollEdgeAppearance = bar
        UINavigationBar.appearance().tintColor = .black

        if let tab = DUIFont.uiFont(11, weight: .medium) {
            let item = UITabBarItemAppearance()
            for state in [item.normal, item.selected] {
                state.titleTextAttributes = [.font: tab]
            }
            item.normal.iconColor = UIColor(white: 0.42, alpha: 1) // grey-600
            item.normal.titleTextAttributes[.foregroundColor] = UIColor(white: 0.42, alpha: 1)
            item.selected.iconColor = .black
            item.selected.titleTextAttributes[.foregroundColor] = UIColor.black
            let tabs = UITabBarAppearance()
            tabs.configureWithDefaultBackground()
            tabs.stackedLayoutAppearance = item
            tabs.inlineLayoutAppearance = item
            tabs.compactInlineLayoutAppearance = item
            UITabBar.appearance().standardAppearance = tabs
            UITabBar.appearance().scrollEdgeAppearance = tabs
        }
        UITabBar.appearance().tintColor = .black
    }
}
