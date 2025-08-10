<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="BookYourCoach API",
 *      description="API REST pour la plateforme de réservation de cours avec coaches équestres ou autres sports. Cette API permet la gestion complète des utilisateurs (admin/enseignants/élèves), des réservations, des paiements et de la facturation.",
 *      @OA\Contact(
 *          name="BookYourCoach Support",
 *          email="support@bookyourcoach.com",
 *          url="https://bookyourcoach.com"
 *      ),
 *      @OA\License(
 *          name="MIT License",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Serveur de développement BookYourCoach"
 * )
 * 
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      description="Authentification par token Bearer. Utilisez le token obtenu via /api/auth/login"
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
