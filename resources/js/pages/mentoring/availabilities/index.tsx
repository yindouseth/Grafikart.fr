import { CalendarDaysIcon, PlusIcon, SaveIcon, XIcon } from "lucide-react"
import { useMemo, useState } from "react"
import MentoringAvailabilityController from "@/actions/App/Http/Cms/MentoringAvailabilityController.ts"
import { Form, useFormError } from "@/components/form.tsx"
import { withLayout } from "@/components/layout.tsx"
import { PageTitle } from "@/components/page-title.tsx"
import { Button } from "@/components/ui/button.tsx"
import { Input } from "@/components/ui/input.tsx"
import type { MentoringAvailabilityData } from "@/types"
import { Badge } from "@/components/ui/badge.tsx"

type Props = {
  availabilities: MentoringAvailabilityData[]
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
  ({ availabilities }) => {
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
      <div className="max-w-2xl">
        <PageTitle>Disponibilités mentoring</PageTitle>{" "}
        <h1 className="flex items-center gap-2 font-semibold text-xl mb-2">
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
                  {dayRanges.map((range, rangeIndex) => (
                    <AvailabilityRangeRow
                      day={day}
                      key={rangeIndex}
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
