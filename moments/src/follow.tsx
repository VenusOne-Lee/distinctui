import { Collapse, Count, Stage, States, type Act } from '../kit'

// Follow: one press, the pill turns outlined in its own footprint, the
// count rolls a beat later, and a quiet line says what following means.

type S = { following: boolean; count: boolean; note: boolean }

const initial: S = { following: false, count: false, note: false }
const poster: S = { following: true, count: true, note: true }

async function script(a: Act<S>) {
  await a.wait(700)
  await a.move({ at: 'follow', x: 0.74, y: 0.62 })
  await a.wait(250)
  await a.click()
  a.put({ following: true })
  await a.wait(380)
  a.put({ count: true })
  await a.wait(500)
  a.put({ note: true })
  await a.move({ x: 350, y: 236 })
  await a.wait(2400)
}

export function Follow() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-follow" cursor={{ at: { x: 360, y: 250 } }}>
      {(s) => (
        <div className="mo-card mo-follow__card">
          <div className="mo-follow__who">
            <span className="dui-mark" data-ring="2" style={{ ['--mark-size' as string]: '60px' }}>
              <span className="dui-mark__inner">SN</span>
              <span className="dui-mark__version">v0.4</span>
            </span>
            <div className="mo-live__title">
              <b>Sipho Ndlovu</b>
              <span>Cape Town · 3 projects</span>
            </div>
          </div>
          <div className="mo-follow__bar">
            <span className="mo-pill mo-pill--wide" data-target="follow" data-cursor="pointer" data-outline={s.following || undefined}>
              <States value={s.following ? 'on' : 'off'} states={{ off: <span className="mo-pill__label">Follow</span>, on: <span className="mo-pill__label">Following</span> }} />
            </span>
            <div className="mo-follow__count">
              <b className="mo-num"><Count value={s.count ? 128 : 127} /></b>
              <span className="mo-faint">followers</span>
            </div>
          </div>
          <Collapse open={s.note} reveal="drop">
            <p className="mo-note">You'll hear when Sipho ships.</p>
          </Collapse>
        </div>
      )}
    </Stage>
  )
}
