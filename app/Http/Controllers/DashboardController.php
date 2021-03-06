<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Agent;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(){
    	$agents = Agent::join('agent_closing', 'agents.id', 'agent_closing.agent_id')->join('closings', 'agent_closing.closing_id', 'closings.id')->groupBy('agents.id')->selectRaw('*, count(*) as closing_count, agents.nama as nama_agent, agents.id as id_agent')->where('tanggal', '>=', Carbon::now()->startOfMonth())->where('tanggal', '<=', Carbon::now()->lastOfMonth())->orderBy('closing_count', 'desc')->paginate(5);
    	return view('dashboard.index', compact('agents'));
    }
}
