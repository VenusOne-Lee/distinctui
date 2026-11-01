import { Count, Stage, States, type Act } from '../kit'

// Avatars and a badge, with no cursor: three empty seats wait on a team,
// each one fills as an invite is accepted, the count rolls with it, and the
// badge settles on Full when the last seat is taken.

type S = { filled: number; done: boolean }

const initial: S = { filled: 0, done: false }
const poster: S = { filled: 3, done: true }
const PEOPLE = [
  { initials: 'AK', ring: '3' },
  { initials: 'LM', ring: '4' },
  { initials: 'ZD', ring: '1' },
]

async function script(a: Act<S>) {
  await a.wait(900)
  for (let i = 1; i <= 3; i++) {
    a.put({ filled: i })
    await a.wait(i < 3 ? 900 : 600)
  }
  a.put({ done: true })
  await a.wait(2400)
}

export function Invite() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-ask">
      {(s) => (
        <div className="mo-card mo-ask__card">
          <div className="mo-ask__top">
            <span className="mo-faint">Team seats</span>
            <span className="mo-badge" data-solid={s.done || undefined}>
              <States value={s.done ? 'done' : 'open'} states={{ open: <span>Open</span>, done: <span>Full</span> }} />
            </span>
          </div>
          <p className="mo-ask__q">Design team, <b>three seats</b></p>
          <div className="mo-ask__spots">
            {PEOPLE.map((p, i) => (
              <span key={p.initials} className="mo-spot" data-on={s.filled > i || undefined}>
                <span className="dui-mark dui-mark--empty" style={{ ['--mark-size' as string]: '52px' }}>
                  <span className="dui-mark__inner">+</span>
                </span>
                <span className="dui-mark" data-ring={p.ring} style={{ ['--mark-size' as string]: '52px' }}>
                  <span className="dui-mark__inner">{p.initials}</span>
                </span>
              </span>
            ))}
          </div>
          <p className="mo-ask__count">
            <b className="mo-num"><Count value={s.filled} /></b> of 3 joined
          </p>
        </div>
      )}
    </Stage>
  )
}
