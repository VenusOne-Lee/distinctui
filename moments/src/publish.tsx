import { Bar, Check, Collapse, Count, Roll, Stage, States, Window, type Act } from '../kit'

// Publish: someone presses Publish, the button settles on itself, the mark's
// dashes fill in with one pop, the version rolls, the Draft badge becomes
// Live, a row arrives at the top of the activity list and a new subscriber
// lands a beat later. Plays once when seen; the poster is the payoff.

type S = { pressed: boolean; busy: boolean; live: boolean; row: boolean; sub: boolean }

const initial: S = { pressed: false, busy: false, live: false, row: false, sub: false }
const poster: S = { pressed: true, busy: true, live: true, row: true, sub: true }

async function script(a: Act<S>) {
  await a.wait(700)
  await a.move({ at: 'go', x: 0.78, y: 0.62 })
  await a.wait(250)
  await a.click()
  a.put({ pressed: true, busy: true })
  await a.wait(650)
  a.put({ live: true })
  await a.wait(420)
  a.put({ row: true })
  await a.wait(900)
  a.put({ sub: true })
  await a.move(a.layout === 'compact' ? { x: 60, y: 440 } : { x: 650, y: 290 })
  await a.wait(2400)
}

export function Publish() {
  return (
    <Stage
      width={720}
      height={340}
      compact={{ below: 600, width: 358, height: 460 }}
      initial={initial}
      poster={poster}
      script={script}
      play="once"
      palette="mono"
      className="dui-look mo mo-live"
      cursor={{ at: { wide: { x: 640, y: 120 }, compact: { x: 320, y: 330 } } }}
    >
      {(s, _rect, layout) => (
        <>
          <Window url="yourproduct.app/projects/harbour" className="mo-live__win">
            <div className="mo-live__body">
              {layout === 'wide' && (
                <aside className="mo-live__side">
                  <Bar w={52} h={8} tone={4} />
                  <Bar w={60} h={6} tone={3} />
                  <Bar w={44} h={6} tone={3} />
                  <Bar w={56} h={6} tone={2} />
                </aside>
              )}
              <main className="mo-live__main">
                <header className="mo-live__head">
                  <span className={`dui-mark${s.live ? ' dui-live-now' : ''}`} data-live={s.live ? 'true' : 'false'} data-ring="2" style={{ ['--mark-size' as string]: '64px' }}>
                    <span className="dui-mark__inner">H</span>
                    <span className="dui-mark__version"><Roll>{s.live ? 'v1.0' : 'v0.9'}</Roll></span>
                  </span>
                  <div className="mo-live__title">
                    <b>Harbour</b>
                    <span className="mo-live__sub">
                      <span className="mo-badge mo-badge--sm" data-solid={s.live || undefined}>
                        <States value={s.live ? 'live' : 'draft'} states={{ draft: <span>Draft</span>, live: <span>Live</span> }} />
                      </span>
                      Bookings
                    </span>
                  </div>
                  <span className="mo-pill" data-target="go" data-cursor="pointer" data-done={s.live || undefined}>
                    <States
                      value={s.live ? 'done' : s.busy ? 'busy' : 'idle'}
                      states={{
                        idle: <span className="mo-pill__label">Publish</span>,
                        busy: <span className="mo-pill__label"><Check on={false} pending size={14} /> Publishing</span>,
                        done: <span className="mo-pill__label"><Check on size={14} /> Published</span>,
                      }}
                    />
                  </span>
                </header>
                <p className="mo-live__label">Activity</p>
                <div className="mo-live__rows">
                  <Collapse open={s.row}>
                    <div className="mo-row mo-row--new">
                      <b>v1.0 published</b>
                      <span>Just now</span>
                    </div>
                  </Collapse>
                  <div className="mo-row">
                    <b>v0.9 sent to 12 testers</b>
                    <span>3 days ago</span>
                  </div>
                  <div className="mo-row">
                    <Bar w={160} h={7} tone={3} />
                    <Bar w={56} h={6} tone={2} />
                  </div>
                  <div className="mo-row">
                    <Bar w={120} h={7} tone={2} />
                    <Bar w={48} h={6} tone={2} />
                  </div>
                </div>
              </main>
            </div>
          </Window>
          <div className="mo-live__sat" data-show={s.sub || undefined}>
            <span className="dui-mark dui-mark--plain" style={{ ['--mark-size' as string]: '36px' }}>
              <span className="dui-mark__inner">JR</span>
            </span>
            <div>
              <span className="mo-faint">Subscribers</span>
              <b className="mo-num"><Count value={s.sub ? 1285 : 1284} /></b>
            </div>
          </div>
        </>
      )}
    </Stage>
  )
}
