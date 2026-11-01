import { Count, Stage, States, type Act } from '../kit'

// Tabs and a card: Yearly is pressed, the open tab moves, the price rolls
// down only the digits that change, and the line under it says what you save.

type S = { yearly: boolean; price: boolean; note: boolean }

const initial: S = { yearly: false, price: false, note: false }
const poster: S = { yearly: true, price: true, note: true }

async function script(a: Act<S>) {
  await a.wait(700)
  await a.move({ at: 'yearly', x: 0.5, y: 0.6 })
  await a.wait(250)
  await a.click()
  a.put({ yearly: true })
  await a.wait(260)
  a.put({ price: true })
  await a.wait(520)
  a.put({ note: true })
  await a.move({ x: 360, y: 250 })
  await a.wait(2400)
}

export function Pricing() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-price" cursor={{ at: { x: 360, y: 250 } }}>
      {(s) => (
        <div className="mo-card mo-price__card">
          <div className="dui-tabs" role="presentation">
            <span className="dui-tab" aria-selected={!s.yearly}>Monthly</span>
            <span className="dui-tab" aria-selected={s.yearly} data-target="yearly" data-cursor="pointer">Yearly</span>
          </div>
          <p className="mo-faint mo-price__plan">Team plan</p>
          <p className="mo-price__num">
            <b className="mo-num"><Count value={s.price ? 159 : 199} format={(n) => `R${Math.round(n)}`} /></b>
            <span className="mo-faint">a month</span>
          </p>
          <div className="mo-price__lane">
            <States
              value={s.note ? 'yearly' : 'monthly'}
              align="start"
              states={{
                monthly: <span className="mo-faint">Billed every month. Cancel any time.</span>,
                yearly: <span className="mo-price__save">Billed yearly. You save R480.</span>,
              }}
            />
          </div>
        </div>
      )}
    </Stage>
  )
}
