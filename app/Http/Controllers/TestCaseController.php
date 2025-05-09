<?php
namespace App\Http\Controllers;

use App\Models\TestCase;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TestCaseExport;
use App\Exports\TestCaseSelectedExport;
use Illuminate\Routing\Controller;

use App\Models\Category;
use App\Models\Type;


class TestCaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 200); // Ambil perPage dari URL, default 10 kalau kosong
        $query = TestCase::with(['category', 'jenis']); // Mengambil test case beserta relasi category dan jenis

        // Jika ada filter kategori yang dipilih
        if ($category = $request->get('category')) {
            $query->where('kategori', $category);
        }

        // Jika ada pencarian
        if ($search = $request->get('search')) {
            $query->where('nama_test_case', 'like', "%$search%")
                ->orWhere('kategori', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%")
                ->orWhere('steps', 'like', "%$search%")
                ->orWhere('expected_result', 'like', "%$search%");
        }

        // Paginate hasil query
        $testCases = $query->paginate($perPage)->appends($request->except('page'));

        // Ambil data kategori untuk dropdown
        $categories = Category::all();

        return view('admin.testcases.index', compact('testCases', 'categories'));
    }

    public function create()
    {
        // Ambil data kategori dan tipe dari database
        $category = Category::all(); // Ganti dengan model kategori Anda
        $type = Type::all(); // Ganti dengan model tipe Anda

        $lastTestcase = TestCase::orderBy('nomor', 'desc')->first();
        $nextNomor = $lastTestcase ? $lastTestcase->nomor + 1 : 1;

        return view('admin.testcases.create', compact('nextNomor', 'category', 'type'));

    }//baru ditambah

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nomor' => 'required|integer',
            'kategori' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'nama_test_case' => 'required|string|max:255',
            'steps' => 'required|string',
            'test_data' => 'nullable|string',
            'expected_result' => 'required|string',
        ]);

        TestCase::create($request->all());

        return redirect()->route('admin.testcases.index')
            ->with('success', 'Test Case created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TestCase $testcase)
    {
        $category = Category::all(); // Ambil semua kategori
        $type = Type::all(); // Ambil semua tipe

        return view('admin.testcases.edit', compact('testcase', 'category', 'type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TestCase $testcase)
    {
        $request->validate([
            'nomor' => 'required|integer',
            'kategori' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'nama_test_case' => 'required|string|max:255',
            'steps' => 'required|string',
            'test_data' => 'nullable|string',
            'expected_result' => 'required|string',
        ]);

        $testcase->update($request->all());

        return redirect()->route('admin.testcases.index')
            ->with('success', 'Test Case updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TestCase $testcase)
    {
        $testcase->delete();

        return redirect()->route('admin.testcases.index')
            ->with('success', 'Test Case deleted successfully.');
    }

    /**
     * Export all test cases to Excel.
     */
    public function export()
    {
        return Excel::download(new TestCaseExport, 'testcases.xlsx');
    }

    /**
     * Export selected test cases to Excel.
     */
    public function exportSelected(Request $request)
    {
        $ids = $request->input('selected_testcases');

        if (!$ids) {
            return back()->with('error', 'Please select at least one Test Case to export.');
        }

        return Excel::download(new TestCaseSelectedExport($ids), 'selected_testcases.xlsx');
    }
}