<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="description" content="EasyNest est une application web basée sur Laravel et Vue.js, qui permet aux utilisateurs de réaliser l'optimisation de la découpe de matériaux.">

        <title>EasyNest</title>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
        @vite('resources/js/app.js') <!-- Assurez-vous que Vite est bien configuré pour charger le fichier JS -->
    </head>
    <body style=" background-color: #1e293b;">
        @inertia
    </body>
</html>