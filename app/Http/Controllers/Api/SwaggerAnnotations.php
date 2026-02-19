<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Info(
 *     title="Ramadhan API Documentation",
 *     version="1.0.0",
 *     description="API untuk tracking ibadah Ramadhan, manajemen user, dan fitur islami",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="Ramadhan API Team"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api",
 *     description="Local Development Server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.example.com",
 *     description="Production Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 * 
 * @OA\PathItem(path="/api")
 * @OA\PathItem(path="/auth")
 * @OA\PathItem(path="/user")
 * @OA\PathItem(path="/ramadhan")
 * @OA\PathItem(path="/islamic")
 * @OA\PathItem(path="/api/auth")
 * @OA\PathItem(path="/api/user")
 * @OA\PathItem(path="/api/ramadhan")
 * @OA\PathItem(path="/api/islamic")
 * @OA\PathItem(path="/api/auth/register")
 * @OA\PathItem(path="/api/auth/login")
 * @OA\PathItem(path="/api/auth/logout")
 * @OA\PathItem(path="/api/auth/me")
 * @OA\PathItem(path="/api/user/profile")
 * @OA\PathItem(path="/api/user/avatar")
 * @OA\PathItem(path="/api/user/password")
 * @OA\PathItem(path="/api/user/devices")
 * @OA\PathItem(path="/api/user/logout-all")
 * @OA\PathItem(path="/api/user/account")
 * @OA\PathItem(path="/api/ramadhan/daily")
 * @OA\PathItem(path="/api/ramadhan/quran-logs")
 * @OA\PathItem(path="/api/ramadhan/dzikir-logs")
 * @OA\PathItem(path="/api/ramadhan/summary")
 * @OA\PathItem(path="/api/ramadhan/bookmarks")
 * @OA\PathItem(path="/api/islamic/quran")
 * @OA\PathItem(path="/api/islamic/sholat")
 * @OA\PathItem(path="/api/islamic/doa")
 * @OA\PathItem(path="/api/islamic/kiblat") 
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints untuk registrasi, login, dan logout"
 * )
 * @OA\Tag(
 *     name="User Profile",
 *     description="API Endpoints untuk manajemen profil user"
 * )
 * @OA\Tag(
 *     name="Device Management",
 *     description="API Endpoints untuk manajemen perangkat"
 * )
 * @OA\Tag(
 *     name="Ramadhan - Daily",
 *     description="API Endpoints untuk catatan harian Ramadhan"
 * )
 * @OA\Tag(
 *     name="Ramadhan - Quran Logs",
 *     description="API Endpoints untuk log bacaan Quran"
 * )
 * @OA\Tag(
 *     name="Ramadhan - Dzikir Logs",
 *     description="API Endpoints untuk log dzikir"
 * )
 * @OA\Tag(
 *     name="Ramadhan - Summary",
 *     description="API Endpoints untuk ringkasan dan statistik"
 * )
 * @OA\Tag(
 *     name="Ramadhan - Bookmarks",
 *     description="API Endpoints untuk bookmark ayat"
 * )
 * @OA\Tag(
 *     name="Islamic - Quran",
 *     description="API Endpoints untuk data Al-Quran"
 * )
 * @OA\Tag(
 *     name="Islamic - Sholat",
 *     description="API Endpoints untuk jadwal sholat"
 * )
 * @OA\Tag(
 *     name="Islamic - Doa",
 *     description="API Endpoints untuk doa-doa"
 * )
 * @OA\Tag(
 *     name="Islamic - Kiblat",
 *     description="API Endpoints untuk arah kiblat"
 * )
 */
class SwaggerAnnotations
{
    // File ini hanya untuk anotasi Swagger
}