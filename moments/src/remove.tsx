import { Collapse, Count, Stage, type Act } from '../kit'

// A dialog and a list: Remove asks first, on a white veil, never a dark one.
// Remove it closes the dialog, the row fades and its room closes, and the
// count rolls down.

type S = { ask: boolean; gone: boolean }

const initial: S = { ask: false, gone: false }
const poster: S = { ask: false, gone: true }
const FILES = [
  { name: 'brief.pdf', size: '240 KB' },
  { name: 'logo-old.svg', size: '18 KB', target: true },
  { name: 'notes.txt', size: '4 KB' },
]

async function script(a: Act<S>) {
  await a.wait(700)
  await a.move({ at: 'remove', x: 0.5, y: 0.55 })
  await a.wait(250)
  await a.click()
  a.put({ ask: true })
  await a.wait(900)
  await a.move({ at: 'confirm', x: 0.5, y: 0.6 })
  await a.wait(200)
  await a.click()
  a.put({ ask: false })
  await a.wait(260)
  a.put({ gone: true })
  await a.move({ x: 370, y: 250 })
  await a.wait(2400)
}

export function Remove() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-remove" cursor={{ at: { x: 370, y: 250 } }}>
      {(s) => (
        <>
          <div className="mo-card mo-remove__card">
            <p className="mo-remove__title">Files <span className="mo-faint"><Count value={s.gone ? 2 : 3} /></span></p>
            {FILES.map((f) => {
              const row = (
                <div className="mo-row">
                  <span className="mo-ink">{f.name} <span className="mo-faint">{f.size}</span></span>
                  {f.target ? <span className="mo-ghost" data-target="remove" data-cursor="pointer">Remove</span> : <span className="mo-ghost mo-ghost--quiet">Remove</span>}
                </div>
              )
              return f.target ? <Collapse key={f.name} open={!s.gone}>{row}</Collapse> : <div key={f.name}>{row}</div>
            })}
          </div>
          <div className="mo-veil" data-show={s.ask || undefined}>
            <div className="mo-dialog">
              <b>Remove logo-old.svg?</b>
              <p>It goes for good.</p>
              <div className="mo-dialog__actions">
                <span className="mo-pill" data-target="confirm" data-cursor="pointer">Remove it</span>
                <span className="mo-pill" data-outline>Keep it</span>
              </div>
            </div>
          </div>
        </>
      )}
    </Stage>
  )
}
