<?php
class JsonResponse
{
    public function success($message = "Success", $data = []): void
    {
        http_response_code(200);
        echo json_encode(["message" => $message, "data" => $data]);
    }

    public function created($message = "Resource created successfully"): void
    {
        http_response_code(201);
        echo json_encode(["message" => $message]);
    }

    public function badRequest($message = "Bad Request"): void
    {
        http_response_code(400);
        echo json_encode(["error" => $message]);
    }

    public function unauthorized($message = "Unauthorized access"): void
    {
        http_response_code(401);
        echo json_encode(["error" => $message]);
    }

    public function forbidden($message = "Forbidden"): void
    {
        http_response_code(403);
        echo json_encode(["error" => $message]);
    }

    public function notFound($message = "Resource not found"): void
    {
        http_response_code(404);
        echo json_encode(["error" => $message]);
    }

    public function methodNotAllowed($allowed_methods = "GET, POST"): void
    {
        http_response_code(405);
        header("Allow: $allowed_methods");
        echo json_encode(["error" => "Method not allowed. Allowed: $allowed_methods"]);
    }

    public function unprocessableEntity($errors): void
    {
        http_response_code(422);
        echo json_encode(["errors" => $errors]);
    }

    public function serverError($message = "Internal Server Error"): void
    {
        http_response_code(500);
        echo json_encode(["error" => $message]);
    }

    public function conflict($message = "Conflict detected"): void
    {
        http_response_code(409);
        echo json_encode(["error" => $message]);
    }

    public function tooManyRequests($message = "Too many requests"): void
    {
        http_response_code(429);
        echo json_encode(["error" => $message]);
    }

    public function noContent(): void
    {
        http_response_code(204);
    }
}
