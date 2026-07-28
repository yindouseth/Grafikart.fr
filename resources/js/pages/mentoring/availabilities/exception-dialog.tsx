import { format } from "date-fns";
import { fr } from "date-fns/locale";
import { PlusIcon, XIcon } from "lucide-react";
import { useState } from "react";
import MentoringExceptionController from "@/actions/App/Http/Cms/MentoringExceptionController.ts";
import { Form } from "@/components/form.tsx";
import { Button } from "@/components/ui/button.tsx";
import { Calendar } from "@/components/ui/calendar.tsx";
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from "@/components/ui/dialog.tsx";
import { Input } from "@/components/ui/input.tsx";

type Availability = {
	id: string;
	startsAtMinute: number;
	endsAtMinute: number;
};

const toTime = (minutes: number) =>
	`${String(Math.floor(minutes / 60)).padStart(2, "0")}:${String(minutes % 60).padStart(2, "0")}`;

export function ExceptionDialog() {
	const [open, setOpen] = useState(false);
	const [dates, setDates] = useState<Date[]>([]);
	const [availabilities, setAvailabilities] = useState<Availability[]>([]);

	const reset = () => {
		setDates([]);
		setAvailabilities([]);
	};

	const setDialogOpen = (nextOpen: boolean) => {
		setOpen(nextOpen);
		if (!nextOpen) reset();
	};

	const addAvailability = () => {
		setAvailabilities((current) => [
			...current,
			{ id: crypto.randomUUID(), startsAtMinute: 600, endsAtMinute: 1020 },
		]);
	};

	const updateAvailability = (id: string, changes: Partial<Availability>) => {
		setAvailabilities((current) =>
			current.map((item) => (item.id === id ? { ...item, ...changes } : item)),
		);
	};

	const removeAvailability = (id: string) => {
		setAvailabilities((current) => current.filter((item) => item.id !== id));
	};

	return (
		<Dialog open={open} onOpenChange={setDialogOpen}>
			<Button onClick={() => setOpen(true)} type="button">
				<PlusIcon /> Heures
			</Button>
			<DialogContent className="max-w-100" showCloseButton={false}>
				<DialogHeader>
					<DialogTitle>Sélectionnez les dates</DialogTitle>
				</DialogHeader>
				<Form
					{...MentoringExceptionController.store.form()}
					className="space-y-6"
					onSuccess={() => setDialogOpen(false)}
				>
					{dates.map((date) => (
						<input
							key={date.toISOString()}
							name="dates[]"
							type="hidden"
							value={format(date, "yyyy-MM-dd")}
						/>
					))}
					<Calendar
						locale={fr}
						className="w-full"
						mode="multiple"
						today={new Date()}
						onSelect={(selected) => setDates(selected ?? [])}
						selected={dates}
					/>

					<section className="space-y-3 border-t pt-5">
						<div className="flex items-center justify-between">
							<h3 className="font-semibold text-sm">
								Quelles sont vos disponibilités ?
							</h3>
							<Button
								aria-label="Ajouter une plage horaire spécifique"
								onClick={addAvailability}
								size="icon"
								type="button"
								variant="ghost"
							>
								<PlusIcon />
							</Button>
						</div>
						{availabilities.length === 0 && (
							<p className="text-muted-foreground text-sm">
								Aucune plage : ces dates seront indisponibles.
							</p>
						)}
						{availabilities.map((availability, index) => (
							<AvailabilityRow
								availability={availability}
								index={index}
								key={availability.id}
								onChange={updateAvailability}
								onRemove={removeAvailability}
							/>
						))}
					</section>
					<DialogFooter className="-mx-6 -mb-6 px-6">
						<Button
							onClick={() => setDialogOpen(false)}
							type="button"
							variant="outline"
						>
							Annuler
						</Button>
						<Button disabled={dates.length === 0} type="submit">
							Appliquer
						</Button>
					</DialogFooter>
				</Form>
			</DialogContent>
		</Dialog>
	);
}

function AvailabilityRow({
	availability,
	index,
	onChange,
	onRemove,
}: {
	availability: Availability;
	index: number;
	onChange: (id: string, changes: Partial<Availability>) => void;
	onRemove: (id: string) => void;
}) {
	return (
		<div className="flex items-center gap-2">
			<TimeInput
				name={`availabilities.${index}.startsAtMinute`}
				onChange={(startsAtMinute) =>
					onChange(availability.id, { startsAtMinute })
				}
				value={availability.startsAtMinute}
			/>
			<span aria-hidden="true">–</span>
			<TimeInput
				name={`availabilities.${index}.endsAtMinute`}
				onChange={(endsAtMinute) => onChange(availability.id, { endsAtMinute })}
				value={availability.endsAtMinute}
			/>
			<Button
				aria-label="Supprimer la plage horaire spécifique"
				onClick={() => onRemove(availability.id)}
				size="icon"
				type="button"
				variant="ghost"
			>
				<XIcon />
			</Button>
		</div>
	);
}

function TimeInput({
	name,
	onChange,
	value,
}: {
	name: string;
	onChange: (minutes: number) => void;
	value: number;
}) {
	return (
		<div className="w-24">
			<input name={name} type="hidden" value={value} />
			<Input
				onChange={(event) =>
					onChange(event.currentTarget.valueAsNumber / 60_000)
				}
				step="900"
				type="time"
				value={toTime(value)}
			/>
		</div>
	);
}
