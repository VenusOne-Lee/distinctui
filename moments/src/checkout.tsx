import { Collapse, Count, Stage, States, type Act } from '../kit'

// Steps and a summary: Continue is pressed, delivery joins the summary and
// carries the total down, the total rolls, the steps move on to Pay and the
// button says what the next press does.

type S = { pressed: boolean; row: boolean; total: boolean; step: boolean }

const initial: S = { pressed: false, row: false, total: false, step: false }
const poster: S = { pressed: true, row: true, total: true, step: true }

const rand = (n: number) => `R${Math.round(n).toLocaleString('en-US')}`

async function script(a: Act<S>) {
  await a.wait(700)
  await a.move({ at: 'continue', x: 0.7, y: 0.6 })
  await a.wait(250)
  await a.click()
  a.put({ pressed: true, row: true })
  await a.wait(420)
  a.put({ total: true })
  await a.wait(600)
  a.put({ step: true })
  await a.move({ x: 370, y: 120 })
  await a.wait(2400)
}

export function Checkout() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-checkout" cursor={{ at: { x: 370, y: 120 } }}>
      {(s) => (
        <div className="mo-card mo-checkout__card">
          <ol className="dui-steps mo-checkout__steps">
            <li data-done="true"><span>Bag</span></li>
            <li data-done={s.step ? 'true' : undefined} aria-current={s.step ? undefined : 'step'}><span>Delivery</span></li>
            <li aria-current={s.step ? 'step' : undefined}><span>Pay</span></li>
          </ol>
          <div className="mo-checkout__rows">
            <div className="mo-row"><span className="mo-ink">Speckled mug × 2</span><span className="mo-ink">R640</span></div>
            <Collapse open={s.row}>
              <div className="mo-row"><span className="mo-ink">Courier, Thursday</span><span className="mo-ink">R60</span></div>
            </Collapse>
            <div className="mo-row mo-row--total"><b>Total</b><b className="mo-num"><Count value={s.total ? 700 : 640} format={rand} /></b></div>
          </div>
          <span className="mo-pill mo-pill--full" data-target="continue" data-cursor="pointer">
            <States
              value={s.step ? 'pay' : 'idle'}
              states={{
                idle: <span className="mo-pill__label">Continue</span>,
                pay: <span className="mo-pill__label">Pay R700</span>,
              }}
            />
          </span>
        </div>
      )}
    </Stage>
  )
}
