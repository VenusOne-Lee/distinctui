// swift-tools-version:5.9
// distinctUI for SwiftUI: the same tokens, type, pills and signature mark as the CSS.
import PackageDescription

let package = Package(
    name: "DistinctUI",
    platforms: [.iOS(.v17)],
    products: [
        .library(name: "DistinctUI", targets: ["DistinctUI"]),
        .library(name: "DistinctUIExamples", targets: ["DistinctUIExamples"]),
    ],
    targets: [
        .target(
            name: "DistinctUI",
            path: "swift/Sources/DistinctUI",
            resources: [.copy("Resources/BricolageGrotesque.ttf"), .copy("Resources/OFL.txt")]
        ),
        .target(
            name: "DistinctUIExamples",
            dependencies: ["DistinctUI"],
            path: "swift/Sources/DistinctUIExamples",
            resources: [.process("Resources")]
        ),
    ]
)
