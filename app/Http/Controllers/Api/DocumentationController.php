<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    /**
     * Display the API documentation page.
     */
    public function index(): Response
    {
        return Inertia::render('Api/Documentation', [
            'specUrl' => '/api-docs/openapi.yaml',
        ]);
    }

    /**
     * Return the OpenAPI specification as JSON.
     */
    public function spec()
    {
        $yamlPath = public_path('api-docs/openapi.yaml');

        if (!file_exists($yamlPath)) {
            abort(404, 'API specification not found');
        }

        $yaml = file_get_contents($yamlPath);

        // Convert YAML to JSON if needed
        if (request()->wantsJson() || request()->has('format') && request('format') === 'json') {
            $spec = yaml_parse($yaml);
            return response()->json($spec);
        }

        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }
}
