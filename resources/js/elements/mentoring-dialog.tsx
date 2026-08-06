import { useEffect, useMemo, useState } from "react"
import "temporal-polyfill/global"
import { onAll } from "@/lib/dom.ts"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "@/components/ui/dialog.tsx"
import { apiFetch, queryClient, useApiFetch } from "@/hooks/use-api-fetch.ts"
import { QueryClientProvider } from "@tanstack/react-query"
import type { MentoringAvailabilityData } from "@/types"
import { Calendar } from "@/components/ui/calendar.tsx"
import { Button } from "@/components/ui/button.tsx"
import {
  ArrowLeftIcon,
  CalendarIcon,
  GlobeIcon,
  LoaderCircleIcon,
  PencilIcon,
} from "lucide-react"
import { FormField } from "@/components/form-field.tsx"
import { Field } from "@/components/ui/field.tsx"

const userTimeZone = Temporal.Now.timeZoneId()

function dateFromKey(dateKey: string) {
  const date = Temporal.PlainDate.from(dateKey)
  return new Date(date.year, date.month - 1, date.day)
}

function dateKeyFromDate(date: Date) {
  return Temporal.PlainDate.from({
    year: date.getFullYear(),
    month: date.getMonth() + 1,
    day: date.getDate(),
  }).toString()
}

function formatDate(dateKey: string, options: Intl.DateTimeFormatOptions) {
  return Temporal.PlainDate.from(dateKey).toLocaleString("fr-FR", options)
}

function formatTime(startTime: string) {
  return Temporal.Instant.from(startTime)
    .toZonedDateTimeISO(userTimeZone)
    .toLocaleString("fr-FR", {
      hour: "2-digit",
      minute: "2-digit",
    })
}

function formatSessionDate(startTime: string) {
  const start =
    Temporal.Instant.from(startTime).toZonedDateTimeISO(userTimeZone)
  const end = start.add({ hours: 1 })

  return `${start.toLocaleString("fr-FR", {
    weekday: "long",
    day: "numeric",
    month: "long",
  })} de ${start.toLocaleString("fr-FR", { hour: "2-digit", minute: "2-digit" })} à ${end.toLocaleString("fr-FR", { hour: "2-digit", minute: "2-digit" })}`
}

export function MentoringDialog() {
  const [open, setOpen] = useState(false)
  const [slotTime, setSlotTime] = useState<null | string>(null)

  useEffect(() => {
    return onAll(document.body, ".js-mentoring", "click", () => {
      setOpen(true)
    })
  }, [])

  return (
    <QueryClientProvider client={queryClient}>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="lg:max-w-200 gap-0 p-0">
          {slotTime ? (
            <SubjectForm date={slotTime} />
          ) : (
            <SlotPicker onChange={setSlotTime} value={slotTime} />
          )}
        </DialogContent>
      </Dialog>
    </QueryClientProvider>
  )
}

/**
 * Form to select a subject
 */
function SubjectForm({ date }: { date: string }) {
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState<string>()

  return (
    <div>
      <div className="border-b px-5 py-5 sm:px-8">
        <DialogTitle className="pr-8 text-xl sm:text-2xl">
          Mentorat le {formatSessionDate(date)}
          <Button size="icon" variant="ghost" className="text-muted">
            <PencilIcon />
          </Button>
        </DialogTitle>
        <DialogDescription className="mt-1">
          Afin de préparer la session indiquez les points que vous voulez
          évoquer pendant la session
        </DialogDescription>
      </div>
      <form
        className="p-8 text-muted-foreground grid gap-4"
        onSubmit={async (event) => {
          event.preventDefault()
          setIsSubmitting(true)
          setError(undefined)
          const form = new FormData(event.currentTarget)
          try {
            const response = await apiFetch<{ url: string }>(
              "/api/mentoring/bookings",
              {
                method: "POST",
                body: JSON.stringify(Object.fromEntries(form)),
              },
            )
            window.location.assign(response.url)
          } catch (error) {
            setError(
              error instanceof Error
                ? error.message
                : "Impossible de démarrer le paiement.",
            )
            setIsSubmitting(false)
          }
        }}
      >
        <input type="hidden" value={date} name="slot" />
        <FormField
          label="Sujet"
          name="subject"
          placeholder="Arthictecture front end"
        />
        <FormField
          label="Description"
          name="description"
          placeholder=""
          type="textarea"
        />
        {error && <p className="text-sm text-destructive">{error}</p>}
        <Field orientation="horizontal" className="justify-end">
          <Button size="lg" disabled={isSubmitting}>
            <CalendarIcon />
            {isSubmitting ? "Redirection…" : "Réserver"}
          </Button>
        </Field>
      </form>
    </div>
  )
}

/**
 * Display a calendar and slot picker to select a time for a mentoring session
 */
function SlotPicker({
  onChange,
  value,
}: {
  onChange: (s: string) => void
  value: string | null
}) {
  const [selectedDate, setSelectedDate] = useState<string>()

  const { data, isFetching } = useApiFetch<MentoringAvailabilityData[]>(
    "/api/mentoring/availabilities",
  )
  const availabilityByDate = useMemo(() => {
    return new Map(
      data?.map((availability) => [availability.date, availability.startTimes]),
    )
  }, [data])

  const availableDates = useMemo(
    () => new Set(availabilityByDate.keys()),
    [availabilityByDate],
  )
  const firstAvailableDate = data?.[0]?.date
  const slots = selectedDate ? (availabilityByDate.get(selectedDate) ?? []) : []

  return (
    <div>
      <div className="border-b px-5 py-5 sm:px-8">
        <DialogTitle className="pr-8 text-xl sm:text-2xl">
          Choisissez une date et un créneau
        </DialogTitle>
        <DialogDescription className="mt-1">
          Réservez votre session de mentorat.
        </DialogDescription>
      </div>

      {isFetching ? (
        <div className="flex min-h-80 items-center justify-center text-muted-foreground">
          <LoaderCircleIcon className="mr-2 size-5 animate-spin" />
          Chargement des disponibilités…
        </div>
      ) : data?.length === 0 ? (
        <p className="p-8 text-muted-foreground">
          Aucun créneau n’est disponible pour le moment.
        </p>
      ) : (
        <div className="grid md:grid-cols-[1fr_300px]">
          {/* Date selector */}
          <section
            className={
              selectedDate ? "hidden p-4 sm:p-8 md:block" : "p-4 sm:p-8"
            }
          >
            <Calendar
              mode="single"
              selected={selectedDate ? dateFromKey(selectedDate) : undefined}
              onSelect={(date) => {
                if (!date) return
                setSelectedDate(dateKeyFromDate(date))
              }}
              defaultMonth={
                firstAvailableDate ? dateFromKey(firstAvailableDate) : undefined
              }
              disabled={(date) => !availableDates.has(dateKeyFromDate(date))}
              className="mx-auto w-full max-w-md p-0"
              classNames={{
                today: "bg-list-hover",
                day_button: "rounded-full hover:text-white",
              }}
            />
            <div className="mt-6 flex items-center gap-2 text-sm text-muted-foreground">
              <GlobeIcon className="size-4" />
              <span>Heure locale ({userTimeZone})</span>
            </div>
          </section>

          {/* Time selector */}
          <section
            className={
              selectedDate
                ? "border-t px-5 py-6 md:max-h-128 md:overflow-y-auto md:border-t-0 md:border-l sm:px-8"
                : "hidden border-t px-5 py-6 md:block md:max-h-128 md:overflow-y-auto md:border-t-0 md:border-l sm:px-8"
            }
          >
            <div className="mb-5 flex items-center gap-2">
              <Button
                variant="ghost"
                size="icon-sm"
                className="md:hidden"
                onClick={() => setSelectedDate(undefined)}
              >
                <ArrowLeftIcon />
                <span className="sr-only">Changer de date</span>
              </Button>
              <h2 className="text-base font-semibold capitalize">
                {selectedDate
                  ? formatDate(selectedDate, {
                      weekday: "long",
                      day: "numeric",
                      month: "long",
                    })
                  : "Choisissez une date"}
              </h2>
            </div>

            <div className="grid gap-3">
              {slots.map((startTime) => (
                <Button
                  key={startTime}
                  variant="outline"
                  className="h-12 justify-center border-primary/60 text-base font-semibold text-primary hover:border-primary hover:bg-primary hover:text-primary-foreground aria-pressed:border-primary aria-pressed:bg-primary aria-pressed:text-primary-foreground"
                  aria-pressed={value === startTime}
                  onClick={() => onChange(startTime)}
                >
                  {formatTime(startTime)}
                </Button>
              ))}
            </div>
          </section>
        </div>
      )}
    </div>
  )
}
