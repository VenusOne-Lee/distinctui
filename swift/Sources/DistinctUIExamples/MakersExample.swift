import DistinctUI
import SwiftUI

/// A directory: pill tabs, cards of the same shape, one note, and an empty state.
public struct MakersExample: View {
    @State private var filter = "All"

    public init() {}

    public var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: DUISpace.x6) {
                DUITitle("Everything", bold: "shipped")
                    .padding(.top, DUISpace.x2)

                DUITabs(selection: $filter, options: ["All", "Live", "Beta", "Needs help"].map { (value: $0, label: $0) })

                VStack(spacing: DUISpace.x4) {
                    ForEach(shown) { project in
                        NavigationLink {
                            ProfileExample()
                        } label: {
                            ProjectCard(project: project)
                        }
                        .buttonStyle(.duiCard)
                    }
                }

                VStack(alignment: .leading, spacing: DUISpace.x4) {
                    DUITitle("From people you", bold: "follow", role: .h2)
                    DUIEmptyState("When someone you follow ships a new version, it shows up here.", actionTitle: "Find makers to follow") {}
                }
                .padding(.top, DUISpace.x6)
            }
            .duiScreen()
            .padding(.bottom, DUISpace.section)
        }
        .navigationTitle("Makers")
        .navigationBarTitleDisplayMode(.inline)
    }

    private var shown: [Project] {
        switch filter {
        case "Live": Project.all.filter(\.live)
        case "Beta": Project.all.filter { $0.status == "Beta" }
        case "Needs help": Project.all.filter { $0.needs != nil }
        default: Project.all
        }
    }
}

struct ProjectCard: View {
    let project: Project

    var body: some View {
        VStack(alignment: .leading, spacing: DUISpace.x4) {
            HStack(spacing: DUISpace.x3) {
                project.maker.mark(size: 44, live: project.live)
                VStack(alignment: .leading, spacing: 0) {
                    Text(project.maker.name).duiFont(15, weight: .semibold, relativeTo: .subheadline)
                    Text("\(project.maker.city) · \(project.version)").duiText(.meta)
                }
                .lineLimit(1)
                Spacer(minLength: DUISpace.x2)
                DUIBadge(project.status, style: project.id == "spaza" ? .solid : .outline)
            }

            VStack(alignment: .leading, spacing: DUISpace.x2) {
                Text(project.name)
                    .duiText(.h3)
                    .lineLimit(2)
                    .multilineTextAlignment(.leading)
                Text(project.oneLine)
                    .duiText(.body, colour: .duiGrey800)
                    .lineLimit(3)
                    .multilineTextAlignment(.leading)
            }

            if let needs = project.needs {
                Text("**Needs:** \(needs)").duiNote()
            }
        }
        .foregroundStyle(Color.duiBlack)
    }
}

#Preview {
    NavigationStack { MakersExample() }.distinctUI()
}
