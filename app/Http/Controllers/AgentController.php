<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Agent;
use App\Cabang;
use JavaScript;

class AgentController extends Controller
{
    public function index(Request $request){
        $agents = null;
        if($request->search == null){
            $agents = Agent::where('nama', 'not like', 'kantor%')->paginate(5);
        }else{
            $agents = Agent::where('nama', 'like',  $request->search.'%')->where('nama','not like', 'kantor%')->paginate(5);
            $agents->appends(['search' => $request->search]);
        }
        return view('agent.index', compact('agents'));
    }

    public function add(Request $request){
        if(Cabang::all()->count() == 0){
            $request->session()->flash('status', 'Please Make a New Branch First Before Adding New Agent');
            return redirect('agent');
        }
        $agents = Agent::where('nama', 'not like', 'kantor%')->where('status', true)->get();
        if(Agent::where('nama', 'not like', 'kantor%')->count() > 0 && $agents->count() == 0){
            $request->session()->flash('status', 'You Need At Least One Employed Agent Before Adding New Agent');
            return redirect('agent');
        }
        $cabangs = Cabang::all();

        return view('agent.add', compact('agents', 'cabangs'));
    }

    public function register(Request $request){
        $this->validate($request,[
            'name' => 'required',
            'location' => 'required', 
            'phone' => 'required|numeric|digits_between:10,12',
            'branch' => 'required']);
        if(Agent::where('nama', 'not like', 'kantor%')->count() > 0){
            $this->validate($request, [
                'upline' => 'required|not_in:0,1']);
            if($request->upline == 0){
                return redirect('agent/add')->withErrors(['upline', 'The selected upline is invalid.']);;
            }
        }
        $agent = new Agent;
        $agent->nama = $request->name;
        $agent->lokasi = $request->location;
        $agent->telepon = $request->phone;
        $agent->cabang_id = $request->branch;
        if($request->upline > 1){
            $agent->upline_id = $request->upline;
        }
        $agent->save();

        return redirect('agent/view/'.$agent->id);
    }

    public function view($id){
    	if(!is_numeric($id)){
    		return redirect('agent');
    	}

        $agent = Agent::find($id);

        if($agent == null){
            return redirect('agent');
        }else if(strpos($agent->nama, 'Kantor') !== false){
            return redirect('agent');
        }

        $button = 'btn btn-outline-primary';

        $firstAgent = Agent::where('nama', 'not like', 'kantor%')->first();

        if(!$firstAgent->isEmployed){
            $button = 'btn btn-outline-danger';
        }

        if($id == $firstAgent->id){
            $button = $button .' this-agent';
            if(!$firstAgent->isEmployed){
                $button = $button .' dipecat';
            }
        }else {
            if(!$firstAgent->isEmployed){
                $button = $button .' outline-dipecat';
            }
        }

        $tree = [
            'chart' => [
                'container' => '#agent-tree',
                'connectors' => [
                    'type' => 'step'
                ]
            ],
            'nodeStructure' => [
                'text' => [
                    'name' => $firstAgent->nama,
                    'title' => $firstAgent->cabang->nama
                ],
                'link' => [
                    'href' => route('agent.view', ['id' => $firstAgent->id])
                ],
                'HTMLclass' => $button,
                'children' => $this->getAllDownlines($firstAgent->id, $id)
            ]
        ];

        JavaScript::put([
            'agent_tree' => json_encode($tree),
            'nama' => $agent->nama
        ]);

        return view('agent.view', compact('agent'));
    }

    private function getAllDownlines($upline_id, $selectedId){
        $upline = Agent::find($upline_id);

        $children = array();

        foreach ($upline->downline as $downline) {
            $button = 'btn btn-outline-primary';

            if(!$downline->isEmployed){
                $button = 'btn btn-outline-danger';
            }

            if($selectedId == $downline->id){
                $button = $button.' this-agent';
                if(!$downline->isEmployed){
                    $button = $button .' dipecat';
                }
            }else {
                if(!$downline->isEmployed){
                    $button = $button .' outline-dipecat';
                }
            }
            
            if($downline->downline->count() == 0){
                $children[] = [
                    'text' => [
                        'name' => $downline->nama,
                        'title' => $downline->cabang->nama
                    ],
                    'link' => [
                        'href' => route('agent.view', ['id' => $downline->id])
                    ],
                    'HTMLclass' => $button
                ];
            }else{
                $children[] = [
                    'text' => [
                        'name' => $downline->nama,
                        'title' => $downline->cabang->nama
                    ],
                    'link' => [
                        'href' => route('agent.view', ['id' => $downline->id])
                    ],
                    'HTMLclass' => $button,
                    'children' => $this->getAllDownlines($downline->id, $selectedId)
                ];
            }
        }

        return $children;
    }

    public function edit($id){
    	if(!is_numeric($id)){
    		return redirect('agent');
    	}else if ($id == 1){
            return redirect('agent');
        }

        $agent = Agent::find($id);
        
        if($agent == null){
            return redirect('agent');
        }else if(strpos($agent->nama, 'Kantor') !== false){
            return redirect('agent');
        }

        $cabangs = Cabang::all();

        return view('agent.edit', compact('agent', 'cabangs'));
    }

    public function change(Request $request, $id){
        if(!is_numeric($id)){
            return redirect('agent');
        }else if ($id == 1){
            return redirect('agent');
        }
        
        $agent = Agent::find($id);
        
        if($agent == null){
            return redirect('agent');
        }else if(strpos($agent->nama, 'Kantor') !== false){
            return redirect('agent');
        }

        $this->validate($request,[
            'name' => 'required',
            'location' => 'required', 
            'phone' => 'required|numeric|digits_between:10,12',
            'branch' => 'required']);

        $agent->nama = $request->name;
        $agent->lokasi = $request->location;
        $agent->telepon = $request->phone;
        if($agent->isPrincipal || $agent->isVice){
            if($agent->cabang_id != $request->branch){
                if($agent->isPrincipal){
                    $agent->cabang->principal_id = null;
                }else if ($agent->isVice){
                    $agent->cabang->vice_id = null;
                }
            }
        }
        $agent->cabang_id = $request->branch;
        $agent->push();

        return redirect('agent/view/'.$id);
    }

    public function changeStatus($id){
        if(!is_numeric($id)){
            return redirect('agent');
        }else if ($id == 1){
            return redirect('agent');
        }
        
        $agent = Agent::find($id);
        
        if($agent == null){
            return redirect('agent');
        }else if(strpos($agent->nama, 'Kantor') !== false){
            return redirect('agent');
        }

        if($agent->isEmployed){
            if($agent->isPrincipal){
                $agent->cabang->principal_id = null;
            }else if($agent->isVice){
                $agent->cabang->vice_id = null;
            }
        }

        $agent->status = !$agent->status;
        $agent->push();
        
        return redirect('agent/view/'.$id);
    }
}
