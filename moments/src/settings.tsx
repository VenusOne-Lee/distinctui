import { Collapse, Stage, type Act } from '../kit'

// A switch and a notice: the switch saves straight away, and the notice
// opens where the person is looking and stays. distinctUI has no toasts that
// vanish before they're read; this is its "saved" moment.

type S = { on: boolean; saved: boolean }

const initial: S = { on: false, saved: false }
const poster: S = { on: true, saved: true }

async function script(a: Act<S>) {
  await a.wait(700)
  await a.move({ at: 'switch', x: 0.5, y: 0.5 })
  await a.wait(250)
  await a.click()
  a.put({ on: true })
  await a.wait(500)
  a.put({ saved: true })
  await a.move({ x: 360, y: 250 })
  await a.wait(2400)
}

export function Settings() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-settings" cursor={{ at: { x: 360, y: 250 } }}>
      {(s) => (
        <div className="mo-card mo-settings__card">
          <p className="mo-settings__title">Notifications</p>
          <div className="dui-switch mo-settings__row">
            <span>Weekly summary</span>
            <input type="checkbox" role="switch" checked={s.on} readOnly tabIndex={-1} aria-hidden="true" data-target="switch" data-cursor="pointer" />
          </div>
          <Collapse open={s.saved}>
            <p className="dui-status mo-settings__status">Saved. The first one goes out on Monday.</p>
          </Collapse>
          <div className="dui-switch mo-settings__row">
            <span>Remind me before a deadline</span>
            <input type="checkbox" role="switch" checked readOnly tabIndex={-1} aria-hidden="true" />
          </div>
        </div>
      )}
    </Stage>
  )
}
