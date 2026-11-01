import { Check, Field, Stage, States, type Act } from '../kit'

// A field that catches a mistake: the error sits under the field that
// caused it, in its own lane so nothing shifts, leaves the moment the
// value is fixed, and the button confirms on itself.

type S = { link: string; focus: boolean; status: 'none' | 'error' | 'valid'; sending: boolean; done: boolean }

const initial: S = { link: 'northline.studio', focus: false, status: 'none', sending: false, done: false }
const poster: S = { link: 'https://northline.studio', focus: false, status: 'valid', sending: true, done: true }

async function script(a: Act<S>) {
  await a.wait(700)
  await a.move({ at: 'send', x: 0.8, y: 0.62 })
  await a.wait(200)
  await a.click()
  a.put({ status: 'error' })
  await a.wait(1100)
  await a.click({ at: 'link', x: 0.06, y: 0.55 })
  a.put({ focus: true })
  await a.wait(200)
  for (const pre of ['h', 'ht', 'htt', 'http', 'https', 'https:', 'https:/', 'https://']) {
    a.put({ link: pre + 'northline.studio' })
    await a.wait(70)
  }
  a.put({ status: 'valid' })
  await a.wait(600)
  await a.click({ at: 'send', x: 0.8, y: 0.62 })
  a.put({ focus: false, sending: true })
  await a.wait(900)
  a.put({ done: true })
  await a.move({ at: 'send', x: 0.9, y: 1.8 })
  await a.wait(2400)
}

export function FixField() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-fix" cursor={{ at: { x: 360, y: 270 } }}>
      {(s) => (
        <div className="mo-card mo-fix__card">
          <div className="mo-fix__group">
            <Field label="Website" value={s.link} focused={s.focus} status={s.status === 'none' ? undefined : s.status} box={{ 'data-target': 'link', 'data-cursor': 'ibeam' } as never} />
            <div className="mo-fix__lane">
              <States
                value={s.status === 'error' ? 'error' : 'hint'}
                align="start"
                states={{
                  hint: <span className="mo-faint">Where people can find you.</span>,
                  error: <span className="mo-error">Add a link that starts with https://</span>,
                }}
              />
            </div>
          </div>
          <span className="mo-pill mo-pill--full" data-target="send" data-cursor="pointer">
            <States
              value={s.done ? 'done' : s.sending ? 'busy' : 'idle'}
              states={{
                idle: <span className="mo-pill__label">Save changes</span>,
                busy: <span className="mo-pill__label"><Check on={false} pending size={14} /> Saving</span>,
                done: <span className="mo-pill__label"><Check on size={14} /> Saved</span>,
              }}
            />
          </span>
        </div>
      )}
    </Stage>
  )
}
