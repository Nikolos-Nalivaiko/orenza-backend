<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Workspaces\ExportWorkspaceAction;
use App\Actions\Workspaces\SummarizeWorkspaceDataAction;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WorkspaceExportController extends Controller
{
    public function __construct(
        private readonly SummarizeWorkspaceDataAction $summarize,
        private readonly ExportWorkspaceAction $export,
    ) {}

    public function summary(Workspace $workspace): JsonResponse
    {
        $this->authorize('manage', $workspace);

        return ApiResponse::success($this->summarize->handle($workspace));
    }

    public function download(Workspace $workspace): BinaryFileResponse
    {
        $this->authorize('manage', $workspace);

        $path = $this->export->handle($workspace);
        $name = sprintf('orenza-%s-%s.zip', $workspace->slug, now()->toDateString());

        return response()
            ->download($path, $name, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
