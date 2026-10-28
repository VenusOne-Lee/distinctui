import DistinctUI
import SwiftUI

/// A longer form after a failed submit: the problems banner names each field, each field says how to fix it.
public struct FormExample: View {
    enum Stage: String, CaseIterable { case idea = "Idea", beta = "Beta", live = "Live" }

    @State private var name = ""
    @State private var oneLine = "Split a minibus taxi fare without counting coins at the front."
    @State private var link = "taxifare"
    @State private var story = ""
    @State private var stage = Stage.live
    @State private var updates = false
    @State private var checked = true

    public init(showErrors: Bool = true) {
        _checked = State(initialValue: showErrors)
    }

    private var nameError: String? { checked && name.isEmpty ? "Add a name for your project, up to 60 characters." : nil }
    private var linkError: String? { checked && !link.hasPrefix("https://") ? "Start the link with https://, like https://taxifare.co.za." : nil }
    private var problems: [String] {
        [nameError.map { _ in "Project name: add a name" }, linkError.map { _ in "Link to try it: start it with https://" }].compactMap { $0 }
    }

    public var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: DUISpace.x6) {
                DUITitle("Share what you've", bold: "shipped")
                    .padding(.top, DUISpace.x2)

                if !problems.isEmpty {
                    DUIFormProblems(problems)
                }

                DUIField("Project name", error: nameError) {
                    DUITextField("Project name", text: $name, prompt: "Taxi Fare", invalid: nameError != nil)
                }
                DUIField("One line", hint: "What it does, for whom. Up to 90 characters.") {
                    DUITextField("One line", text: $oneLine)
                    Text("\(oneLine.count) / 90")
                        .duiFont(12, relativeTo: .caption1)
                        .monospacedDigit()
                        .foregroundStyle(Color.duiGrey600)
                        .frame(maxWidth: .infinity, alignment: .trailing)
                }
                DUIField("Link to try it", error: linkError) {
                    DUITextField("Link to try it", text: $link, prompt: "https://", invalid: linkError != nil)
                        .textInputAutocapitalization(.never)
                        .keyboardType(.URL)
                }
                DUIField("The story", optional: true) {
                    DUITextField("The story", text: $story, prompt: "Why you built it and who helped", multiline: true)
                }

                DUIField("Where is it?") {
                    VStack(spacing: DUISpace.x2) {
                        DUIChoice("Idea", detail: "Nothing to try yet. Your mark stays dashed.", selected: stage == .idea) { stage = .idea }
                        DUIChoice("Beta", detail: "Some people can try it.", selected: stage == .beta) { stage = .beta }
                        DUIChoice("Live", detail: "Anyone can try it. Your mark goes solid.", selected: stage == .live) { stage = .live }
                    }
                }

                Toggle("Email me when someone asks to help. You can stop these any time.", isOn: $updates)
                    .toggleStyle(.duiCheckbox)

                VStack(alignment: .leading, spacing: DUISpace.x3) {
                    Button("Share it") { checked = true }
                        .buttonStyle(.dui(fullWidth: true))
                    Button("Save as a draft") {}
                        .buttonStyle(.dui(.secondary, fullWidth: true))
                }
                .padding(.top, DUISpace.x2)
            }
            .duiScreen()
            .padding(.bottom, DUISpace.section)
        }
        .navigationTitle("Share")
        .navigationBarTitleDisplayMode(.inline)
    }
}

#Preview {
    NavigationStack { FormExample() }.distinctUI()
}
