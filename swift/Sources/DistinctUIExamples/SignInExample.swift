import DistinctUI
import SwiftUI

/// Sign in with an emailed link or a passkey. One black pill, the passkey outlined, the notice link last.
public struct SignInExample: View {
    @State private var email = ""
    @State private var sent = false
    @State private var problem: String?

    public init() {}

    public var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: DUISpace.x8) {
                DUILogo("makers")
                    .padding(.top, DUISpace.x6)

                VStack(alignment: .leading, spacing: DUISpace.x3) {
                    DUITitle("Welcome", bold: "back", role: .hero)
                    Text("We'll email you a link. No password to remember.")
                        .duiText(.lede)
                        .fixedSize(horizontal: false, vertical: true)
                }

                if sent {
                    DUIStatus("Check your email. The link works for 15 minutes.")
                }

                VStack(alignment: .leading, spacing: DUISpace.x4) {
                    DUIField("Email", error: problem) {
                        DUITextField("Email", text: $email, prompt: "you@example.co.za", shape: .pill, invalid: problem != nil)
                            .textContentType(.emailAddress)
                            .keyboardType(.emailAddress)
                            .textInputAutocapitalization(.never)
                            .autocorrectionDisabled()
                    }
                    Button("Email me a link") {
                        if email.contains("@") {
                            problem = nil
                            sent = true
                        } else {
                            problem = "Add your email address, like you@example.co.za."
                        }
                    }
                    .buttonStyle(.dui(fullWidth: true))

                    Button {} label: {
                        Label("Use a passkey", systemImage: "person.badge.key")
                    }
                    .buttonStyle(.dui(.secondary, fullWidth: true))
                }

                Text("New here? Joining is by invite. We use your email to sign you in and nothing else. [How we use it](https://example.com/privacy)")
                    .duiText(.meta)
                    .tint(.duiBlack)
                    .fixedSize(horizontal: false, vertical: true)
            }
            .duiScreen()
            .padding(.bottom, DUISpace.section)
        }
        .scrollDismissesKeyboard(.interactively)
    }
}

#Preview {
    SignInExample().distinctUI()
}
