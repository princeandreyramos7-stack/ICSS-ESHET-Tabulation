import * as React from "react"

const MOBILE_BREAKPOINT = 768
const MOBILE_QUERY = `(max-width: ${MOBILE_BREAKPOINT - 1}px)`

/**
 * True below Tailwind's `md` breakpoint. Answered by the same media query the
 * stylesheet uses (not window.innerWidth, which mobile Safari reports from the
 * visual viewport and which can disagree with CSS while zoomed).
 */
export function isMobileViewport() {
  if (typeof window === "undefined" || typeof window.matchMedia !== "function") return false
  return window.matchMedia(MOBILE_QUERY).matches
}

export function useIsMobile() {
  // Correct on the very first render so the sidebar never starts in the wrong mode.
  const [isMobile, setIsMobile] = React.useState(isMobileViewport)

  React.useEffect(() => {
    if (typeof window.matchMedia !== "function") return undefined
    const mql = window.matchMedia(MOBILE_QUERY)
    const onChange = (event) => setIsMobile(event.matches)
    setIsMobile(mql.matches)
    // Older Safari (< 14) only has the addListener API.
    if (typeof mql.addEventListener === "function") {
      mql.addEventListener("change", onChange)
      return () => mql.removeEventListener("change", onChange)
    }
    mql.addListener(onChange)
    return () => mql.removeListener(onChange)
  }, [])

  return isMobile
}
