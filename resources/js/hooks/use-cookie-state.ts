import { useState } from "react"
import { cookie } from "@/lib/cookie.ts"

export function useCookieState(key: string, duration: number) {
  const [isSet, setisSet] = useState(() => cookie(key) !== null)

  const setCookieState = (value: boolean) => {
    cookie(key, value ? "true" : null, { expires: duration })
    setisSet(value)
  }

  return [isSet, setCookieState] as const
}
