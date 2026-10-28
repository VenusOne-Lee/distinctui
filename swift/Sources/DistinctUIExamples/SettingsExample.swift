import DistinctUI
import SwiftUI

/// Account, emails, your data and deleting the account. Switches are the system's, tinted black.
public struct SettingsExample: View {
    @State private var helpAsks = true
    @State private var weekly = false
    @State private var news = false

    public init() {}

    public var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: DUISpace.section) {
                HStack(spacing: DUISpace.x4) {
                    Maker.kagiso.mark(size: 64, version: "v1.2")
                    VStack(alignment: .leading, spacing: 2) {
                        Text(Maker.kagiso.name).duiText(.h4)
                        Text("kagiso@example.co.za").duiText(.meta)
                    }
                }
                .padding(.top, DUISpace.x4)

                section("Your", bold: "profile") {
                    DUIRows {
                        row("Name and photo", value: "Kagiso Molefe")
                        row("City", value: Maker.kagiso.city)
                        row("Ring colour", value: "Vermilion")
                    }
                }

                section("", bold: "Emails", after: " we send") {
                    VStack(alignment: .leading, spacing: 0) {
                        toggle("Someone offers to help", detail: "Only when you need to act.", isOn: $helpAsks)
                        toggle("Friday round-up", detail: "What shipped this week.", isOn: $weekly)
                        toggle("News from us", detail: "A few times a year.", isOn: $news)
                    }
                }

                section("Your", bold: "data") {
                    VStack(alignment: .leading, spacing: DUISpace.x4) {
                        Text("Download everything we hold about you as one file, or delete your account and all of it.")
                            .duiText(.body, colour: .duiGrey800)
                            .fixedSize(horizontal: false, vertical: true)
                        Button("Download my data") {}
                            .buttonStyle(.dui(.secondary, size: .small))
                        // Deleting is black like everything else. Weight and a confirm step carry the warning.
                        Button("Delete my account") {}
                            .buttonStyle(.duiLink)
                    }
                    .duiPanel()
                }
            }
            .duiScreen()
            .padding(.bottom, DUISpace.section)
        }
        .navigationTitle("Settings")
        .navigationBarTitleDisplayMode(.inline)
    }

    private func section<Content: View>(_ before: String, bold: String, after: String = "", @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: DUISpace.x4) {
            DUITitle(before, bold: bold, after: after, role: .h2)
            content()
        }
    }

    private func row(_ label: String, value: String) -> some View {
        Button {} label: {
            DUIRow {
                Text(label).duiFont(16, weight: .medium, relativeTo: .callout)
            } trailing: {
                HStack(spacing: DUISpace.x2) {
                    Text(value).duiText(.meta).lineLimit(1)
                    DUIChevron()
                }
            }
        }
        .buttonStyle(.plain)
    }

    private func toggle(_ title: String, detail: String, isOn: Binding<Bool>) -> some View {
        DUIRow {
            Toggle(isOn: isOn) {
                VStack(alignment: .leading, spacing: 2) {
                    Text(title).duiFont(16, weight: .medium, relativeTo: .callout)
                    Text(detail).duiText(.meta)
                }
            }
            .tint(.duiBlack)
        }
    }
}

#Preview {
    NavigationStack { SettingsExample() }.distinctUI()
}
