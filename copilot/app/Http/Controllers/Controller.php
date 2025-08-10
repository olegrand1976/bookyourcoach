<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="BookYourCoach API",
 *      description="API REST pour la plateforme de réservation de cours avec coaches",
 *      @OA\Contact(
 *          email="admin@bookyourcoach.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Serveur de développement"
 * )
 * 
 * @OA\SecurityScheme(
 *      securityScheme="bearerAuth",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      description="Utilisez un token Bearer pour l'authentification"
 * )
 */
abstract class Controller
{
    //
}
