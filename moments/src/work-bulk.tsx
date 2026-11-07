import { Collapse, Count, Stage, type Act } from '../kit'

// distinctUI@work: a bulk action. Two rows are ticked, the views row swaps
// for the black bulk bar, Send for approval changes both stages at once,
// and a notice with Undo stays in the page. No toast.

type S = { ticks: number; sent: boolean; notice: boolean }

const initial: S = { ticks: 0, sent: false, notice: false }
const poster: S = { ticks: 0, sent: true, notice: true }
const ROWS = [
  { name: 'Tyres and fitment', who: 'RN', ring: '5' },
  { name: 'Print and packaging', who: 'NS', ring: '1' },
  { name: 'Forklift leasing', who: 'PV', ring: '4' },
]

async function script(a: Act<S>) {
  await a.wait(700)
  await a.click({ at: 'tick-0', x: 0.5, y: 0.5 })
  a.put({ ticks: 1 })
  await a.wait(300)
  await a.click({ at: 'tick-1', x: 0.5, y: 0.5 })
  a.put({ ticks: 2 })
  await a.wait(600)
  await a.move({ at: 'send', x: 0.5, y: 0.55 })
  await a.wait(200)
  await a.click()
  a.put({ ticks: 0, sent: true })
  await a.wait(420)
  a.put({ notice: true })
  await a.move({ x: 380, y: 250 })
  await a.wait(2400)
}

export function WorkBulk() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-bulk" cursor={{ at: { x: 380, y: 250 } }}>
      {(s) => (
        <div className="mo-card mo-bulk__card">
          <div className="mo-swap" data-selecting={s.ticks > 0 || undefined}>
            <span className="mo-swap__views"><span className="mo-tab" data-on>All</span><span className="mo-tab">Needs me</span></span>
            <span className="mo-swap__bar">
              <span><Count value={Math.max(s.ticks, 1)} /> selected</span>
              <span className="mo-swap__main" data-target="send" data-cursor="pointer">Send for approval</span>
            </span>
          </div>
          {ROWS.map((r, i) => (
            <div key={r.name} className="mo-row mo-bulk__row" data-sel={s.ticks > i && i < 2 ? '' : undefined}>
              <span className="mo-box" data-target={`tick-${i}`} data-cursor="pointer" data-on={(s.ticks > i && i < 2) || undefined} />
              <span className="dui-mark" data-ring={r.ring} style={{ ['--mark-size' as string]: '28px' }}><span className="dui-mark__inner">{r.who}</span></span>
              <span className="mo-ink mo-bulk__name">{r.name}</span>
              <span className="mo-st" data-st={i < 2 && s.sent ? 'review' : i === 2 ? 'signed' : 'draft'}>{i < 2 ? (s.sent ? 'In review' : 'Draft') : 'Signed'}</span>
            </div>
          ))}
          <Collapse open={s.notice}>
            <div className="mo-notice"><span>Sent 2 contracts for approval.</span><b>Undo</b></div>
          </Collapse>
        </div>
      )}
    </Stage>
  )
}
