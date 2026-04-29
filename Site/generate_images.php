<?php
/**
 * Generate sample placeholder images for demonstration
 * Run this script once via browser to create demo images
 */

$images = [
    'img1.jpg' => ['Modern living room', 'Kitchen area', 'Bedroom view'],
    'img2.jpg' => ['Cozy bedroom', 'City view', 'Workspace'],
    'img3.jpg' => ['Balcony', 'Entrance', 'Bathroom'],
    'img4.jpg' => ['Studio setup', 'Minimalist design'],
    'img5.jpg' => ['Nearby attractions', 'Transport hub'],
    'img6.jpg' => ['Penthouse lounge', 'Dining area'],
    'img7.jpg' => ['Master bedroom', 'Jacuzzi'],
    'img8.jpg' => ['Skyline view', 'Rooftop access'],
    'img9.jpg' => ['Family-friendly space', 'Kids area'],
    'img10.jpg' => ['Playground', 'Quiet neighborhood'],
    'img11.jpg' => ['Work desk setup', 'Business amenities'],
];

$dir = __DIR__ . '/images/apartments/';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

echo "Image generator placeholder. In production, place real images in images/apartments/";
?>
