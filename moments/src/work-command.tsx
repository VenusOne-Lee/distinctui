import { Stage, type Act } from '../kit'

// distinctUI@work: the command menu. ⌘K opens it on a white veil, typing
// narrows the list, Enter runs the top match. Keys show as keycaps.

type S = { open: boolean; q: string; done: boolean }

const initial: S = { open: false, q: '', done: false }
const poster: S = { open: true, q: 'assign nal', done: false }
const ALL = [
  { label: 'Assign to Naledi Sithole', words: 'assign naledi procurement', img: 5, ring: '1' },
  { label: 'Assign to Rahul Naidoo', words: 'assign rahul operations', img: 10, ring: '5' },
  { label: 'Go to Contracts', words: 'go contracts' },
  { label: 'Go to Reports', words: 'go reports' },
]

async function script(a: Act<S>) {
  await a.wait(700)
  await a.key('cmd+k')
  a.put({ open: true })
  await a.wait(500)
  await a.type('q', 'assign nal', { interval: 70 })
  await a.wait(500)
  await a.key('enter')
  a.put({ open: false, done: true })
  await a.wait(2600)
}

export function WorkCommand() {
  return (
    <Stage width={400} height={260} initial={initial} poster={poster} script={script} play="once" palette="mono" className="dui-look mo mo-cmd" keys="bottom">
      {(s) => {
        const q = s.q.trim().toLowerCase().split(/\s+/).filter(Boolean)
        const shown = ALL.filter((o) => q.every((w) => o.words.includes(w)))
        return (
          <>
            <div className="mo-card mo-cmd__page">
              <b className="mo-cmd__title">Fleet telematics renewal</b>
              <div className="mo-row"><span className="mo-faint">Owner</span><span className="mo-ink">{s.done ? 'Naledi Sithole' : 'Ayanda Khumalo'}</span></div>
              <div className="mo-row"><span className="mo-faint">Stage</span><span className="mo-ink">In review, 2 of 3</span></div>
              {s.done && <div className="mo-notice mo-notice--in"><span>Assigned to Naledi Sithole.</span><b>Undo</b></div>}
            </div>
            <div className="mo-veil" data-show={s.open || undefined}>
              <div className="mo-cmd__box">
                <div className="mo-cmd__q">{s.q || <span className="mo-faint">Type a command</span>}<span className="mo-caret" /></div>
                {shown.map((o, i) => (
                  <div key={o.label} className="mo-cmd__opt" data-on={i === 0 || undefined}>
                    {o.img ? <span className="dui-mark" data-ring={o.ring} style={{ ['--mark-size' as string]: '28px' }}><span className="dui-mark__inner"><img src={`/examples/img/portrait-${o.img}.jpg`} alt="" /></span></span> : <span className="mo-cmd__arrow">→</span>}
                    {o.label}
                  </div>
                ))}
              </div>
            </div>
          </>
        )
      }}
    </Stage>
  )
}
