import { ArrowRightIcon, XIcon } from "lucide-react"
import { useCookieState } from "@/hooks/use-cookie-state.ts"
import type { MouseEventHandler } from "react"

export function PromoFreelance() {
  const [isDismissed, setIsDismissed] = useCookieState(
    "promo-freelance-dismissed",
    30,
  )

  const dismiss: MouseEventHandler<HTMLButtonElement> = (e) => {
    e.preventDefault()
    setIsDismissed(true)
    document.body.classList.remove("has-promo")
  }

  if (isDismissed) {
    return null
  }

  return (
    <div className="theme-light [body:not(.has-drawer)_&]:container flex justify-between items-center border-primary border bg-primary hover:bg-primary/80 text-white text-primary px-2 py-1 text-sm font-medium relative">
      <div className="items-center flex gap-2">
        <span className="text-white/80">
          Vous cherchez un{" "}
          <strong className="text-white">développeur freelance</strong> pour
          votre prochain projet ?{" "}
        </span>
        <a
          href="https://jonathan-boyer.fr/contact"
          className="flex gap-1 items-center overlay border border-white/70 rounded-full px-2 text-xs py-0.5 bg-white/15"
          target="_blank"
        >
          Demandez un devis
          <ArrowRightIcon className="size-3" />
        </a>
      </div>
      <button
        className="hover:text-white z-2 relative"
        aria-label="Masquer cette promotion"
        onClick={dismiss}
      >
        <XIcon className="size-4" />
      </button>
    </div>
  )
}
