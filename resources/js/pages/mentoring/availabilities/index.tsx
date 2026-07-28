import {
  CalendarDaysIcon,
  PlusIcon,
  SaveIcon,
  TrashIcon,
  XIcon,
} from "lucide-react"
import { useMemo, useState } from "react"
import MentoringAvailabilityController from "@/actions/App/Http/Cms/MentoringAvailabilityController.ts"
import MentoringExceptionController from "@/actions/App/Http/Cms/MentoringExceptionController.ts"
import { Form, useFormError } from "@/components/form.tsx"
import { withLayout } from "@/components/layout.tsx"
import { PageTitle } from "@/components/page-title.tsx"
import { Badge } from "@/components/ui/badge.tsx"
import { Button } from "@/components/ui/button.tsx"
import { ButtonLink } from "@/components/ui/button-link.tsx"
import { Input } from "@/components/ui/input.tsx"
import { ExceptionDialog } from "@/pages/mentoring/availabilities/exception-dialog.tsx"
import type { MentoringAvailabilityData, MentoringExceptionData } from "@/types"
import { cn } from "@/lib/utils.ts"

type Props = {
  availabilities: MentoringAvailabilityData[]
  exceptions: MentoringExceptionData[]
}

type Availability = MentoringAvailabilityData & { id: string }

const days = [
  "Lundi",
  "Mardi",
  "Mercredi",
  "Jeudi",
  "Vendredi",
  "Samedi",
  "Dimanche",
]

const toTime = (minutes: number) =>
  `${String(Math.floor(minutes / 60)).padStart(2, "0")}:${String(minutes % 60).padStart(2, "0")}`

export default withLayout<Props>(
  ({ availabilities, exceptions }) => {
    const [ranges, setRanges] = useState<Availability[]>(() =>
      availabilities.map((availability, index) => ({
        ...availability,
        id: `${availability.weekday}-${index}`,
      })),
    )
    const groupedRanges = useMemo(
      () => Object.groupBy(ranges, ({ weekday }) => weekday),
      [ranges],
    )

    const addRange = (weekday: number) => {
      setRanges((current) => [
        ...current,
        {
          id: crypto.randomUUID(),
          weekday,
          startsAtMinute: 600,
          endsAtMinute: 1020,
        },
      ])
    }

    return (
      <div className="max-w-2xl space-y-8">
        <PageTitle>Disponibilités mentoring</PageTitle>{" "}
        <h1 className="flex items-center gap-2 font-semibold text-xl mb-4">
          <CalendarDaysIcon className="text-primary" />
          Disponibilités
        </h1>
        <Form
          {...MentoringAvailabilityController.update.form()}
          className="space-y-4"
          id="mentoring-availabilities"
        >
          {days.map((day, index) => {
            const weekday = index + 1
            const dayRanges = groupedRanges[weekday] ?? []
            return (
              <section className="flex items-center" key={weekday}>
                <div className="w-20 flex items-center">
                  <Badge variant="secondary">{day}</Badge>
                </div>
                <div className="flex min-w-0 flex-col gap-2">
                  {dayRanges.length === 0 && (
                    <p className="py-2 text-muted-foreground">Indisponible</p>
                  )}
                  {dayRanges.map((range) => (
                    <AvailabilityRangeRow
                      day={day}
                      key={range.id}
                      onRemove={() =>
                        setRanges((current) =>
                          current.filter(({ id }) => id !== range.id),
                        )
                      }
                      range={range}
                    />
                  ))}
                </div>
                <Button
                  aria-label={`Ajouter une plage le ${day}`}
                  onClick={() => addRange(weekday)}
                  size="icon"
                  type="button"
                  variant="ghost"
                >
                  <PlusIcon />
                </Button>
              </section>
            )
          })}
        </Form>
        <section className="space-y-3">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="font-semibold text-lg">Heures spécifiques</h2>
              <p className="text-muted-foreground text-sm">
                Ajustez les disponibilités pour des dates précises.
              </p>
            </div>
            <ExceptionDialog />
          </div>
          <div className="space-y-2">
            {exceptions.length === 0 && (
              <p className="text-muted-foreground text-sm">
                Aucune disponibilité spécifique.
              </p>
            )}
            {exceptions.map((exception) => (
              <ExceptionRow exception={exception} key={exception.date} />
            ))}
          </div>
        </section>
      </div>
    )
  },
  {
    breadcrumb: () => [
      {
        label: "Disponibilités",
        href: MentoringAvailabilityController.index(),
      },
    ],
    top: (
      <Button form="mentoring-availabilities" type="submit">
        <SaveIcon /> Enregistrer
      </Button>
    ),
  },
)

function ExceptionRow({ exception }: { exception: MentoringExceptionData }) {
  const date = new Date(`${exception.date}T12:00:00`)
  const isEmpty = exception.availabilities.length === 0
  const isMultiple = exception.availabilities.length > 1

  return (
    <div className="flex items-start justify-between rounded-lg bg-muted/50 px-4 py-3">
      <div className="font-medium capitalize">
        {date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" })}
      </div>
      <div
        className={cn(
          "flex gap-4 text-sm",
          isMultiple ? "items-start" : "items-center",
        )}
      >
        {isEmpty ? (
          <span className="text-muted-foreground">Indisponible</span>
        ) : (
          exception.availabilities.map((availability) => (
            <p key={availability.startsAtMinute}>
              {toTime(availability.startsAtMinute)} –{" "}
              {toTime(availability.endsAtMinute)}
            </p>
          ))
        )}
        <ButtonLink
          aria-label={`Supprimer la disponibilité spécifique du ${exception.date}`}
          href={MentoringExceptionController.destroy(exception.date)}
          size="icon"
          variant="destructive"
        >
          <TrashIcon />
        </ButtonLink>
      </div>
    </div>
  )
}

function AvailabilityRangeRow({
  day,
  onRemove,
  range,
}: {
  day: string
  onRemove: () => void
  range: Availability
}) {
  return (
    <div className="flex items-center gap-2 w-max">
      <input
        name={`availabilities.${range.id}.weekday`}
        type="hidden"
        value={range.weekday}
      />
      <TimeInput
        defaultValue={range.startsAtMinute}
        name={`availabilities.${range.id}.startsAtMinute`}
      />
      <span aria-hidden="true">–</span>
      <TimeInput
        defaultValue={range.endsAtMinute}
        name={`availabilities.${range.id}.endsAtMinute`}
      />
      <Button
        aria-label={`Supprimer la plage du ${day}`}
        onClick={onRemove}
        size="icon"
        type="button"
        variant="ghost"
      >
        <XIcon />
      </Button>
    </div>
  )
}

function TimeInput({
  defaultValue,
  name,
}: {
  defaultValue: number
  name: string
}) {
  const [value, setValue] = useState(defaultValue)
  const error = useFormError(name)
  console.log(error)
  return (
    <div className="w-24">
      <input type="hidden" name={name} value={value} />
      <Input
        aria-invalid={Boolean(error)}
        step="900"
        type="time"
        onChange={(e) => setValue(e.currentTarget.valueAsNumber / 60_000)}
        defaultValue={toTime(defaultValue)}
      />
      {error && <p className="mt-1 text-destructive text-sm">{error}</p>}
    </div>
  )
}
