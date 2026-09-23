<?php

namespace App\Http\Controllers;

use App\Models\Borne;
use App\Http\Requests\Borne\BorneStoreRequest;
use App\Http\Requests\Borne\BorneUpdateRequest;
use App\Repositories\BorneRepository;
use App\Services\BorneService; // Add BorneService import
use Illuminate\Http\Request;
use App\Exceptions\CrudException; // Add CrudException import

class BorneController extends Controller
{
    private BorneRepository $repository;
    private BorneService $service; // Add BorneService property

    public function __construct(BorneRepository $repository, BorneService $service) // Inject BorneService
    {
        $this->repository = $repository;
        $this->service = $service; // Assign BorneService
        $this->middleware('permission:view_bornes')->only(['index', 'show']);
        $this->middleware('permission:create_bornes')->only(['create', 'store']);
        $this->middleware('permission:edit_bornes')->only(['edit', 'update']);
        $this->middleware('permission:delete_bornes')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $bornes = $this->repository->paginate($request->get('per_page', 15));
        return view('bornes.index', compact('bornes'));
    }

    public function create()
    {
        return view('bornes.create');
    }

    public function store(BorneStoreRequest $request)
    {
        try { // Add try-catch block
            // Use service for creation
            $borne = $this->service->create($request->validated());

            return redirect()
                ->route('bornes.show', $borne)
                ->with('success', 'Borne created successfully.');
        } catch (CrudException $e) { // Catch CrudException
            return redirect()
                ->back()
                ->with('error', $e->getMessage()) // Use the exception message
                ->withInput();
        } catch (\Exception $e) { // Keep generic exception for unexpected errors
            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred while creating the borne.')
                ->withInput();
        }
    }

    public function show(Borne $borne)
    {
        $this->authorize('view', $borne);
        return view('bornes.show', compact('borne'));
    }

    public function edit(Borne $borne)
    {
        $this->authorize('update', $borne);
        return view('bornes.edit', compact('borne'));
    }

    public function update(BorneUpdateRequest $request, Borne $borne)
    {
        $this->authorize('update', $borne);
        try { // Add try-catch block
            // Use service for update
            $this->service->update($borne, $request->validated());

            return redirect()
                ->route('bornes.show', $borne)
                ->with('success', 'Borne updated successfully.');
        } catch (CrudException $e) { // Catch CrudException
            return redirect()
                ->back()
                ->with('error', $e->getMessage()) // Use the exception message
                ->withInput();
        } catch (\Exception $e) { // Keep generic exception for unexpected errors
            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred while updating the borne.')
                ->withInput();
        }
    }

    public function destroy(Borne $borne)
    {
        $this->authorize('delete', $borne);
        try { // Add try-catch block
            // Use service for deletion
            $this->service->delete($borne);

            return redirect()
                ->route('bornes.index')
                ->with('success', 'Borne deleted successfully.');
        } catch (CrudException $e) { // Catch CrudException
            return redirect()
                ->back()
                ->with('error', $e->getMessage()); // Use the exception message
        } catch (\Exception $e) { // Keep generic exception for unexpected errors
            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred while deleting the borne.');
        }
    }
}
