import DistinctUI
import SwiftUI
import UIKit

// The invented people and products the examples use, the same cast as the web templates.
// The photos are AI-generated and show people who don't exist. Replace them with your own.

enum Photo {
    static func named(_ name: String) -> Image {
        guard let url = Bundle.module.url(forResource: name, withExtension: "jpg"),
              let image = UIImage(contentsOfFile: url.path) else { return Image(systemName: "person.fill") }
        return Image(uiImage: image)
    }

    static var naledi: Image { named("portrait-1") }
    static var thando: Image { named("portrait-2") }
    static var kagiso: Image { named("portrait-3") }
    static var ayanda: Image { named("portrait-6") }
    static var spazaStock: Image { named("product-a") }
}

struct Maker: Identifiable {
    let id: String
    let name: String
    let initials: String
    let city: String
    let ring: Int
    let photo: Image?

    var firstName: String { String(name.split(separator: " ").first ?? "") }

    func mark(size: CGFloat, live: Bool = true, version: String? = nil) -> DUIMark {
        DUIMark(photo.map { .photo($0) } ?? .initials(initials), ring: ring, live: live, version: version, size: size)
    }

    static let naledi = Maker(id: "naledi", name: "Naledi Dube", initials: "ND", city: "Pietermaritzburg", ring: 1, photo: Photo.naledi)
    static let kagiso = Maker(id: "kagiso", name: "Kagiso Molefe", initials: "KM", city: "Mahikeng", ring: 2, photo: Photo.kagiso)
    static let lerato = Maker(id: "lerato", name: "Lerato Jacobs", initials: "LJ", city: "Gqeberha", ring: 3, photo: nil)
    static let thando = Maker(id: "thando", name: "Thando Nkosi", initials: "TN", city: "Durban", ring: 4, photo: Photo.thando)
    static let ayanda = Maker(id: "ayanda", name: "Ayanda Khumalo", initials: "AK", city: "Soweto", ring: 5, photo: Photo.ayanda)
}

struct Project: Identifiable {
    let id: String
    let name: String
    let oneLine: String
    let maker: Maker
    let version: String
    let status: String
    let live: Bool
    let needs: String?

    static let all: [Project] = [
        Project(id: "spaza", name: "Spaza Stock", oneLine: "Stock counts for spaza shops, by voice, in isiZulu and English.",
                maker: .naledi, version: "v0.4", status: "Beta", live: false, needs: "testers in KZN"),
        Project(id: "taxi", name: "Taxi Fare", oneLine: "Split a minibus taxi fare without counting coins at the front.",
                maker: .kagiso, version: "v1.2", status: "Live", live: true, needs: nil),
        Project(id: "loadshed", name: "Loadshed Planner, for small bakeries in Gqeberha", oneLine: "Plan oven time around the schedule, with a reminder an hour before.",
                maker: .lerato, version: "v0.1", status: "Idea", live: false, needs: nil),
    ]
}
