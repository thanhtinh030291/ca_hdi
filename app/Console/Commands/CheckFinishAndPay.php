<?php

namespace App\Console\Commands;
use App\Claim;
use App\PaymentHistory;
use App\FinishAndPay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CheckFinishAndPay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:CheckFinishAndPay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dt = Carbon::now();
        $dt_check  = $dt->subDays(10)->format('Y-m-d h:i:s');
        $FinishAndPay = FinishAndPay::where('notify',1)->where('finished', 0)->get();
        $array_update = [];
        foreach ($FinishAndPay as $key => $value) {
            $can_pay_rq = json_decode(json_encode(GetApiMantic('api/rest/plugins/apimanagement/issues/finish/'.$value->mantis_id)),true);
            $can_pay_rq = data_get($can_pay_rq,'status') == 'success' ? 'success' : 'error';
            if($can_pay_rq == 'success'){
                $array_update[] = $value->id;
            }
        }
        if(!empty($array_update)){
            FinishAndPay::whereIn('id',$array_update)->update(['finished' => 1]);
        }
        
        $non_pay = FinishAndPay::where('notify',1)->where('finished', 1)->where('payed', 0)->pluck('cl_no')->toArray();

        $history = PaymentHistory::whereIn('CL_NO', $non_pay)->pluck('CL_NO')->toArray();

        FinishAndPay::whereIn('cl_no', $history)->update(['payed' => 1]);

    }
}
