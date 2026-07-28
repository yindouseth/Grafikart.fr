@extends('front')

@section('title', 'Mentorat')

@section('body')
    <section class="grid md:grid-cols-[1fr_470px] container items-center gap-8 pb-15 bg-background-light">
        <div class="space-y-5 max-w-200">
            <h1 class="text-4xl md:text-6xl font-serif text-foreground-title font-bold">
                Besoin d'aller plus loin ?<br/>
                Découvrez le <span class="text-primary">mentorat</span>
            </h1>
            <p class="text-xl text-pretty">
                Une séance en visioconférence pour avancer concrètement sur votre apprentissage,
                un projet personnel ou professionnel.
            </p>
            <x-atoms.button size="lg" type="button" data-mentoring-booking-trigger>
                <x-lucide-calendar/>
                Réserver une séance
            </x-atoms.button>
        </div>
        <img class="hidden md:block" src="/images/illustrations/podcast.svg" alt="">
    </section>

    <section class="container py-20 grid lg:grid-cols-[1fr_550px] items-center gap-8">
        <div class="max-w-175">
            <h2 class="text-5xl md:text-6xl font-serif font-bold text-foreground-title text-balance">
                Un temps pour faire le point,
                <span class="text-primary">sans rester bloqué seul</span>
            </h2>
            <p class="text-xl md:text-2xl text-muted text-pretty mt-5">
                Vous choisissez le sujet de la séance et venez avec votre contexte, vos questions ou votre code.
                L'objectif est de vous aider à y voir plus clair et à repartir avec des prochaines étapes concrètes.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-5 mt-10 max-w-150 mr-auto">
            <x-atoms.card padded class="space-y-3 md:col-span-2">
                <x-lucide-code-2 class="size-7 text-primary"/>
                <h3 class="text-xl font-bold text-foreground-title">Apprendre plus efficacement</h3>
                <p class="text-muted">Clarifiez une notion, choisissez les bonnes ressources et construisez un plan
                    adapté à votre niveau.</p>
            </x-atoms.card>
            <x-atoms.card padded class="space-y-3">
                <x-lucide-bug class="size-7 text-primary"/>
                <h3 class="text-xl font-bold text-foreground-title">Débloquer un projet</h3>
                <p class="text-muted">Analysez un problème technique, une architecture ou une décision qui vous empêche
                    d'avancer.</p>
            </x-atoms.card>
            <x-atoms.card padded class="space-y-3">
                <x-lucide-compass class="size-7 text-primary"/>
                <h3 class="text-xl font-bold text-foreground-title">Prendre du recul</h3>
                <p class="text-muted">Obtenez un avis sur votre projet pro ou perso et identifiez les priorités les plus
                    utiles.</p>
            </x-atoms.card>
        </div>
    </section>

    <section class="bg-background-light text-center container py-20">
        <div class="max-w-210 mx-auto">
            <h2 class="text-5xl md:text-6xl font-serif font-bold text-foreground-title text-balance">
                Comment se déroule une
                <span class="text-primary">séance ?</span>
            </h2>
            <p class="text-xl text-muted text-pretty mt-5 mb-8">
                La séance se déroule en visioconférence. Pas besoin de préparer un dossier formel : quelques lignes
                sur votre situation et les points que vous souhaitez aborder suffisent.
            </p>
        </div>
        <ol class="space-y-5 max-w-100 mx-auto text-start">
            <li class="flex gap-4">
                <span
                    class="flex-none size-9 rounded-full bg-primary text-primary-foreground font-bold grid place-items-center">1</span>
                <div><h3 class="text-xl font-bold text-foreground-title">Choisissez un créneau</h3>
                    <p class="text-muted mt-1">Sélectionnez une disponibilité qui vous convient.</p></div>
            </li>
            <li class="flex gap-4">
                <span
                    class="flex-none size-9 rounded-full bg-primary text-primary-foreground font-bold grid place-items-center">2</span>
                <div><h3 class="text-xl font-bold text-foreground-title">Partagez votre besoin</h3>
                    <p class="text-muted mt-1">Indiquez le contexte, les difficultés rencontrées et les questions à
                        traiter.</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span
                    class="flex-none size-9 rounded-full bg-primary text-primary-foreground font-bold grid place-items-center">3</span>
                <div><h3 class="text-xl font-bold text-foreground-title">Avancez avec un plan clair</h3>
                    <p class="text-muted mt-1">Repartez avec des pistes concrètes pour continuer votre projet.</p>
                </div>
            </li>
        </ol>

    </section>

    <section class="container py-20 text-center">
        <h2 class="text-5xl md:text-6xl font-serif font-bold text-foreground-title text-balance">
            Prêt à faire avancer votre <span class="text-primary">projet ?</span>
        </h2>
        <p class="text-xl text-muted mt-4 max-w-150 mx-auto">Réservez une heure pour faire le point et trouver la
            meilleure suite à donner.</p>
        <x-atoms.button size="lg" type="button" class="mt-7 mx-auto" data-mentoring-booking-trigger>
            <x-lucide-calendar/>
            Voir les disponibilités
        </x-atoms.button>
    </section>
@endsection
