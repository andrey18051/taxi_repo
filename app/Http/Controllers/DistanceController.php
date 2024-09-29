<?php


namespace App\Http\Controllers;



use App\Helpers\OpenStreetMapHelper;

class DistanceController extends Controller
{
    /**
     * Получить расстояние между двумя точками.
     *
     * @param float $startLat
     * @param float $startLon
     * @param float $endLat
     * @param float $endLon
     */
    public function getDistance(
        float $startLat,
        float $startLon,
        float $endLat,
        float $endLon
    ) {
        // Создаем экземпляр OpenStreetMapHelper
        $osrmHelper = new OpenStreetMapHelper();

        // Получаем расстояние между точками
        return $osrmHelper->getRouteDistance($startLat, $startLon, $endLat, $endLon)/ 1000;
    }
}
