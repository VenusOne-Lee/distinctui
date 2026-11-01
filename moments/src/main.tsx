import { StrictMode, useState, type ComponentType } from 'react'
import { createRoot } from 'react-dom/client'
import { Checkout } from './checkout'
import { FixField } from './fix-field'
import { Invite } from './invite'
import { Pricing } from './pricing'
import { Publish } from './publish'
import { Remove } from './remove'
import { Settings } from './settings'
import './moments.css'

// Mounts each moment into <div data-moment="name">. Moments play once when
// seen (distinctUI motion rule 4); "Play again" replays from the start.
// With reduced motion the poster shows and there is nothing to replay.

const MOMENTS: Record<string, ComponentType> = {
  publish: Publish,
  invite: Invite,
  'fix-field': FixField,
  pricing: Pricing,
  checkout: Checkout,
  settings: Settings,
  remove: Remove,
}
const still = matchMedia('(prefers-reduced-motion: reduce)').matches

function Moment({ name, label }: { name: string; label?: string }) {
  const [take, setTake] = useState(0)
  const Scene = MOMENTS[name]
  return (
    <>
      <div role="img" aria-label={label}>
        <Scene key={take} />
      </div>
      {!still && (
        <button type="button" className="dui-btn-sm-ghost mo-replay" onClick={() => setTake((n) => n + 1)}>
          Play again
        </button>
      )}
    </>
  )
}

document.querySelectorAll<HTMLElement>('[data-moment]').forEach((el) => {
  const name = el.dataset.moment!
  if (!MOMENTS[name]) return
  el.textContent = ''
  createRoot(el).render(
    <StrictMode>
      <Moment name={name} label={el.dataset.label} />
    </StrictMode>,
  )
})
