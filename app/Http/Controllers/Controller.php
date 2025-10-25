<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="activibe API",
 *     version="1.0.0",
 *     description="API REST complète pour la plateforme de réservation de cours avec coaches équestres.
 *     
 * Cette API permet de :
 * - Gérer les utilisateurs (Administrateurs, Enseignants, Élèves)
 * - Planifier et réserver des cours d'équitation
 * - Traiter les paiements via Stripe
 * - Gérer les lieux et types de cours
 * - Suivre les performances et statistiques
 * 
 * ## Authentification
 * L'API utilise l'authentification Bearer Token via Laravel Sanctum.
 * Incluez votre token dans l'header Authorization : `Bearer {token}`
 * 
 * ## Rôles utilisateurs
 * - **Admin** : Accès complet à toutes les fonctionnalités
 * - **Teacher** : Gestion de ses cours, disponibilités et élèves
 * - **Student** : Réservation de cours et gestion de son profil
 * 
 * ## Environnement de test
 * - URL de base : http://localhost:8081/api
 * - Base de données : MySQL via Docker
 * - Paiements : Stripe en mode test
 * - PHPMyAdmin : http://localhost:8082",
 *     termsOfService="https://activibe.com/terms",
 *     @OA\Contact(
 *         name="Équipe activibe",
 *         email="support@activibe.com",
 *         url="https://activibe.com"
 *     ),
 *     @OA\License(
 *         name="Propriétaire - activibe",
 *         url="https://activibe.com/license"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8081/api",
 *     description="Serveur de développement"
 * )
 * 
 * @OA\Server(
 *     url="https://api.activibe.com",
 *     description="Serveur de production"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Authentification via Bearer Token (Laravel Sanctum). 
 *     
 * Pour utiliser l'authentification :
 * 1. Connectez-vous via POST /auth/login
 * 2. Récupérez le token dans la réponse
 * 3. Incluez-le dans l'header: Authorization: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *      name="Authentication",
 *      description="Endpoints d'authentification (login, register, logout)"
 * )
 *
 * @OA\Tag(
 *      name="Users",
 *      description="Gestion des utilisateurs"
 * )
 *
 * @OA\Tag(
 *      name="Profiles",
 *      description="Gestion des profils utilisateurs"
 * )
 *
 * @OA\Tag(
 *      name="Lessons",
 *      description="Gestion des cours et réservations"
 * )
 */
abstract class Controller
{
    //
}
