<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Task Management API",
 *     version="1.0.0",
 *     description="Production-grade Task Management API with authentication, CRUD operations, and webhook notifications",
 *     @OA\Contact(
 *         email="support@taskapi.example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Tasks",
 *     description="Task management endpoints"
 * )
 */
abstract class Controller
{
    //
}
