import { Count, Stage, type Act } from '../kit'

// distinctUI@work: a report. Four black small multiples on one shared scale
// draw in one after another, then the switch turns the charts into their table.
// Never a colour per series.

type S = { drawn: number; table: boolean }

const initial: S = { drawn: 0, table: false }
const poster: S = { drawn: 4, table: false }
const TOP = 2400
const DEPOTS = [
  { name: 'Johannesburg', v: [1820, 1905, 1950, 1985, 2090, 2240] },
  { name: 'Durban', v: [1210, 1230, 1240, 1350, 1420, 1510] },
  { name: 'Cape Town', v: [980, 990, 1020, 1060, 1070, 1080] },
  { name: 'Gqeberha', v: [420, 430, 520, 640, 560, 530] },
]
const W = 150, H = 40
const pts = (v: number[]) => v.map((n, i) => `${(i * W) / (v.length - 1)},${H - (n / TOP) * H}`).join(' ')

async function script(a: Act<S>) {
  await a.wait(500)
  await a.stagger([1, 2, 3, 4], (n) => a.put({ drawn: n }), 260)
  await a.wait(1600)
  await a.click({ at: 'table', x: 0.5, y: 0.5 })
  a.put({ table: true })
  await a.wait(1600)
  await a.click({ at: 'chart', x: 0.5, y: 0.5 })
  a.put({ table: false })
  await a.move({ x: 380, y: 250 })
  await a.wait(2000)
}

export function WorkReport() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-report" cursor={{ at: { x: 380, y: 250 } }}>
      {(s) => (
        <div className="mo-card mo-report__card">
          <div className="mo-report__top">
            <b>Spend by depot</b>
            <span className="mo-seg"><span data-on={!s.table || undefined} data-target="chart" data-cursor="pointer">Chart</span><span data-on={s.table || undefined} data-target="table" data-cursor="pointer">Table</span></span>
          </div>
          {s.table ? (
            <div className="mo-report__table">
              {DEPOTS.map((d) => (
                <div key={d.name} className="mo-row"><span className="mo-ink">{d.name}</span><span className="mo-num">R {(d.v[5] / 1000).toFixed(2)}m</span></div>
              ))}
            </div>
          ) : (
            <div className="mo-report__grid">
              {DEPOTS.map((d, i) => (
                <div key={d.name} className="mo-mult">
                  <span className="mo-mult__head"><span>{d.name}</span><b className="mo-num">R <Count value={s.drawn > i ? d.v[5] / 1000 : 0} format={(n: number) => n.toFixed(2)} />m</b></span>
                  <svg viewBox={`0 -3 ${W} ${H + 6}`}>
                    <polyline className="mo-mult__line" pathLength={1} data-on={s.drawn > i || undefined} points={pts(d.v)} />
                    <line x1="0" x2={W} y1={H} y2={H} className="mo-mult__base" />
                  </svg>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </Stage>
  )
}
