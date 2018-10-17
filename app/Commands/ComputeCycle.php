<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ComputeCycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compute';

    protected $index = 0;

    protected $base = 2;

    protected $exponent = 0;

    protected $budget = 0;

    protected $cycle = 0;

    protected $total_payin = 0;

    protected $total_payout = 0;

    protected $rows;

    protected $head_count_per_level_array;

    protected $total_head_count = 0;

    protected $sum_pay_in;

    protected $sum_payout;

    protected $sum_income;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A Command Line Interface To Project 2x2 Matrix : Head Count, Required Slot to Cycle, Payin/Payout, and Income.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->rows = [];
        $this->head_count_per_level_array = [];

    }

    public function handle()
    {
        $this->info("2x2 Matrix Cycler Simulator Command Line Interface");
        $this->info("\n");
        $this->info("Payin/Payout/Income Projection Based On Having a Complete Perfect 2x2 Matrix Structure.");
        $this->info("\n");
        $this->info("Lets Define Our Inputs");

        while ($this->budget == false || !is_numeric($this->budget)) {
            $this->task("Setting Budget", function () {
                $this->budget = $this->ask('How Much Budget Per Head');
                if (is_numeric($this->budget)) {
                    return true;
                } else {
                    return false;
                }
            });
        }
        $this->task("Budget Set To " . $this->budget, function () {
            return true;
        });
        while ($this->exponent == false || !is_numeric($this->exponent)) {
            $this->task("Setting Matrix Depth", function () {
                $this->exponent = $this->ask('How Deep Is The 2x2 Matrix?');
                if (is_numeric($this->exponent)) {
                    return true;
                } else {
                    return false;
                }
            });
        }
        $this->task("Matrix Level Set To " . $this->exponent, function () {
            return true;
        });
        while ($this->cycle == false || !is_numeric($this->cycle)) {
            $this->task("Setting Matrix Payout Per Cycle", function () {
                $this->cycle = $this->ask('How Much You Want To Payout Per Cycle');

                if (is_numeric($this->cycle)) {
                    return true;
                } else {
                    return false;
                }
            });
        }

        $this->task("Matrix Payout Set to " . $this->cycle, function () {
            return true;
        });

        if ($this->confirm('Do you wish to continue? [y|N]')) {

            while ($this->index < $this->exponent) {
                $head_count_per_level = $this->incrementHeadCount();
                $this->head_count_per_level_array[] = $head_count_per_level;
                $payout_per_level = 0;

                $this->rows[] = [
                    "level" => $this->index + 1,
                    "head_count_per_level" => $head_count_per_level,
                    "required_account_to_cycle" => 0,
                    "payin_per_level" => $this->getPayinPerLevel(),
                    "payout_per_level" => 0,
                    "income" => 0,
                ];
                // Compute Cycle Count Before We Increment Anything
                $this->getRequiredSlotToCycle();
                $this->total_head_count += $this->incrementHeadCount();

                $this->getIncome();

                // we increment everything we need to increment
                $this->sum_pay_in += $this->rows[$this->index]['payin_per_level'];

                $this->sum_payout += $this->rows[$this->index]['payout_per_level'];

                $this->sum_income += $this->rows[$this->index]['income'];
                $this->index++;

            }
            $headers = ['Level', 'Head Count', 'Required Slot To Cycle', 'Required Payin per Level To Cycle', 'Head Count Payout per Level', 'Income'];
            $this->table($headers, $this->rows);

            $total_head_count = $this->rows[$this->index - 1]['required_account_to_cycle'];
            $total_payin = $this->rows[$this->index - 1]['required_account_to_cycle'] * $this->budget;
            $final_row[] = [
                "total_head_count" => $total_head_count,
                "total_payin" => $total_payin,
                "total_payout" => $this->sum_payout,
                "total_income" => $total_payin - $this->sum_payout,
            ];
            $final_header = ['Total Head Count', 'Total Payin', 'Total Payout', 'Total Income'];

            $this->table($final_header, $final_row);
            $message = 'Matrix Overall Income ' . $final_row[0]['total_income'];
            if ($this->sum_income < 0) {
                $message = 'Simulation Shows You Are Bankcrupt!!!';
            }
            $this->notify("Matrix CLI", $message, "matrix.png");

        } else {
            $this->notify("Matrix CLI", "Computation Halted!", "matrix.png");
        }
    }

    private function incrementHeadCount()
    {
        return pow($this->base, $this->index);
    }

    private function getPayinPerLevel()
    {
        $base = 7;
        $multiplier = pow(2, $this->index);
        $total = 0;
        $index = $this->index;
        $total = ($base * $multiplier) + $multiplier - 1;
        while ($index > -1) {
            // reduced total by N times
            $total -= pow(2, $index--);
        }
        return $total * $this->budget;
    }

    private function getRequiredSlotToCycle()
    {
        $base = 7;
        $multiplier = pow(2, $this->index);
        $total = 0;
        $total = ($base * $multiplier) + $multiplier - 1;

        $this->rows[$this->index]['required_account_to_cycle'] = $total;
        $this->rows[$this->index]['payout_per_level'] = $this->rows[$this->index]['head_count_per_level'] * $this->cycle;

    }

    private function getIncome()
    {
        $base = 7;
        $multiplier = pow(2, $this->index);
        $total = 0;
        $index = $this->index;
        $total = ($base * $multiplier) + $multiplier - 1;
        while ($index > -1) {
            // reduced total by N times
            $total -= pow(2, $index--);
        }
        $income = (($total + $this->total_head_count) * $this->budget) - ($this->total_head_count * $this->cycle);
        $this->rows[$this->index]['income'] = $income;
    }

    //! Compute for Adding Extra Account Per Level When We Cycle!

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
