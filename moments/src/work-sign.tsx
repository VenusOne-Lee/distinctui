import { Count, Stage, type Act } from '../kit'

// distinctUI@work: signing. The person who asked sits apart. Two signers are
// solid; the third ring is dashed until she signs, then it goes live, the
// last link turns solid and the count rolls to 3 of 3.

type S = { signed: boolean; pressed: boolean }

const initial: S = { signed: false, pressed: false }
const poster: S = { signed: true, pressed: false }
const SIGNERS = [
  { initials: 'KA', ring: '2', role: 'Legal' },
  { initials: 'PV', ring: '4', role: 'Finance' },
]

async function script(a: Act<S>) {
  await a.wait(800)
  await a.move({ at: 'sign', x: 0.5, y: 0.55 })
  await a.wait(220)
  a.put({ pressed: true })
  await a.click()
  a.put({ pressed: false, signed: true })
  await a.wait(500)
  await a.move({ x: 380, y: 250 })
  await a.wait(2400)
}

export function WorkSign() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-sign" cursor={{ at: { x: 380, y: 250 } }}>
      {(s) => (
        <div className="mo-card mo-sign__card">
          <div className="mo-sign__top">
            <b>Fleet telematics</b>
            <span className="mo-faint"><b className="mo-num"><Count value={s.signed ? 3 : 2} /></b> of 3 signed</span>
          </div>
          <div className="mo-chain" data-done={s.signed || undefined}>
            <span className="mo-chain__from">
              <span className="dui-mark dui-mark--plain" style={{ ['--mark-size' as string]: '40px' }}><span className="dui-mark__inner">AK</span></span>
              <span className="mo-faint">Asked</span>
            </span>
            {SIGNERS.map((p, i) => (
              <span key={p.initials} className="mo-chain__pair">
                <span className="mo-chain__step">
                  <span className="dui-mark" data-live="true" data-ring={p.ring} style={{ ['--mark-size' as string]: '40px' }}><span className="dui-mark__inner">{p.initials}</span></span>
                  <span className="mo-faint">{p.role}</span>
                </span>
                <span className={i === 1 ? 'mo-chain__link mo-chain__link--next' : 'mo-chain__link'} />
              </span>
            ))}
            <span className="mo-chain__step">
              <span className={`dui-mark${s.signed ? ' dui-live-now' : ''}`} data-live={s.signed ? 'true' : 'false'} data-ring="3" style={{ ['--mark-size' as string]: '40px' }}><span className="dui-mark__inner">LD</span></span>
              <span className="mo-faint">You</span>
            </span>
          </div>
          <span className="mo-pill mo-pill--full" data-target="sign" data-cursor="pointer" data-pressed={s.pressed || undefined} data-outline={s.signed || undefined}>
            <span className="mo-pill__label">{s.signed ? 'Signed' : 'Sign as Procurement'}</span>
          </span>
        </div>
      )}
    </Stage>
  )
}
