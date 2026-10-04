<?php
declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Exceptions\FileCannotBeDeletedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileRequest;
use App\Http\Resources\UploadedFileResource;
use App\Http\Traits\SendsStatusResponses;
use App\Models\File;
use App\Services\FileUploadService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Throwable;

class FileController extends Controller
{

    use SendsStatusResponses;

    public function __construct(
        private readonly FileUploadService $uploadService
    )
    {

    }

    /**
     * Show a file
     * @param File $file
     * @return RedirectResponse
     */
    public function show(File $file): RedirectResponse
    {
        //permanent redirect to the file's CDN URL
        return Redirect::to($file->url, 301);
    }

    /**
     * File index
     * @return Renderable
     */
    public function index(): Renderable
    {
        return view('files.index');
    }


    /**
     * File upload form
     * @return Renderable
     */
    public function create(): Renderable
    {
        return view('files.create');
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(UploadFileRequest $request)
    {
        $file = $this->uploadService->storeUploadedFile(
            $request->file('file'),
            $request->user()
        );
        if ($request->expectsJson()) {
            return new UploadedFileResource($file);
        }
        return redirect(route('file.index'));
    }

    /**
     * Remove the specified resource from storage via API
     *
     * @param Request $request
     * @param File $file
     * @return array|RedirectResponse
     * @throws Throwable
     */
    public function destroy(Request $request, File $file): array|RedirectResponse
    {
        $this->authorize('delete', $file);
        try {
            $this->uploadService->delete($file);
        } catch (FileCannotBeDeletedException) {
            return $this->sendResponse($request, route('file.index'), 'File deletion failed');
        }
        return $this->sendResponse($request,
            route('file.index'),
            'Your file was deleted!',
            'file deleted'
        );
    }


    /**
     * GET request to delete the file
     *
     * @param Request $request
     * @param File $file
     * @return Factory|View|Application|\Illuminate\View\View
     */
    public function delete(Request $request, File $file): Factory|View|Application|\Illuminate\View\View
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }
        $this->authorize('delete', $file);
        return view('files.delete', compact('file'));
    }
}
