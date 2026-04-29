# Apartment Images Guide

Place apartment photos in this directory.

## Requirements
- Format: JPG, PNG, or WebP
- Recommended size: 800x600px minimum
- Names can be anything (e.g., `living-room.jpg`, `bedroom-1.png`, `view.jpg`)

## Default Behavior
- The first image in each apartment's image array is used as the thumbnail
- If no images are found, the placeholder image is displayed

## Adding Sample Images
To add images to an apartment via admin:
1. Go to Admin Dashboard → Add/Edit Apartment
2. Enter image filenames in the "Image URLs" field as a JSON array, or manually add them to the `images` JSON field in the database

## Sample Image Filenames
- `apt1-main.jpg`
- `apt1-bedroom.jpg`
- `apt1-kitchen.jpg`
- `apt1-bathroom.jpg`
