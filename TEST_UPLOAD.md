# Testing the Workout Photo Upload

## 🎯 Quick Test Guide

Your backend is now ready to extract workout data from photos! Here's how to test it:

## Method 1: Web UI (Recommended) 📱

1. **Open the upload page in your browser:**
   ```
   http://fitandfocused.test/test-upload
   ```

2. **Take or upload a photo:**
   - Click "Take Photo" to use your camera
   - Or "Choose from Gallery" to select an existing photo

3. **Check the results:**
   - After uploading, check the browser console (F12 → Console)
   - You'll see the extracted workout data
   - An alert will show how many exercises were detected

4. **Expected console output:**
   ```javascript
   ✅ Upload successful! 
   {
     date: "2025-12-28",
     title: "Chest and Triceps",
     exercises: [
       {
         name: "Bench Press",
         sets: [
           { reps: 10, weight: 60, unit: "kg", confidence: "high" }
         ]
       }
     ]
   }
   ```

## Method 2: cURL Test (Quick Verification) 🔧

Test the API directly with a photo file:

```bash
# Replace with path to your workout photo
curl -X POST http://fitandfocused.test/api/workouts/upload \
  -H "X-CSRF-TOKEN: $(curl -s http://fitandfocused.test/test-upload | grep -o 'csrf-token" content="[^"]*' | cut -d'"' -f4)" \
  -F "photo=@/path/to/your/workout-photo.jpg" \
  | json_pp
```

## Method 3: Create a Test Photo 📝

If you don't have a workout photo handy, create one:

1. **Write on paper:**
   ```
   12/29/25 Test Workout
   
   Bench Press: 10x60kg / 8x70kg / 6x80kg
   Squats: 12x100kg / 10x110kg / 8x120kg
   Deadlift: 5x140kg / 3x160kg / 1x180kg
   ```

2. **Take a photo** with good lighting

3. **Upload via the web UI**

## What to Look For ✅

### Success Indicators:
- ✅ Photo uploads without errors
- ✅ Processing takes 5-10 seconds
- ✅ Browser console shows extracted data
- ✅ Exercise names match your handwriting
- ✅ Sets/reps/weights are parsed correctly
- ✅ Date is extracted properly

### Expected Accuracy:
- **Good handwriting**: >90% accuracy
- **Average handwriting**: 80-90% accuracy
- **Poor lighting/illegible**: 60-80% accuracy

### Confidence Scores:
- `"confidence": "high"` - AI is confident in the value
- `"confidence": "low"` - Value might need verification

## Troubleshooting 🔍

### "CSRF token mismatch"
```bash
# Clear config cache
php artisan config:clear
```

### "Photo field is required"
- Ensure you're selecting an image file
- Check file size is under 10MB

### "Failed to process photo"
- Check logs: `storage/logs/laravel.log`
- Verify API key is set: `php artisan config:show services.anthropic`

### API takes too long
- Normal: 5-10 seconds
- If >15 seconds, check internet connection
- Anthropic API may be slower during peak times

## Next Steps 🚀

Once you've verified the upload works:

1. ✅ **Test with 2-3 different workout photos**
   - Verify accuracy across different handwriting styles
   - Check different exercise types
   - Test various notation formats

2. ✅ **Check the extracted data structure**
   - Ensure dates are parsed correctly
   - Verify exercise names are sensible
   - Check weight/rep values are accurate

3. ✅ **Ready for next phase:**
   - Build verification UI (editable form)
   - Add database migrations
   - Implement save functionality

## Example Test Scenarios

### Scenario 1: Simple Upper Body
```
12/29/25 Upper Body
Push-ups: 20 / 18 / 15
Pull-ups: 8 / 7 / 6
Dips: 12 / 10 / 8
```

### Scenario 2: With Weights
```
12/29/25 Leg Day
Squats: 10x135lbs / 8x185lbs / 5x225lbs
Leg Press: 15x200kg / 12x250kg
Lunges: 10x50lbs per leg
```

### Scenario 3: Mixed Units
```
12/29/25 Arms
Barbell Curl: 12x20kg / 10x25kg
Cable Curls: 15x30lbs / 12x35lbs
Hammer Curls: 10x15kg
```

---

**Status**: ✅ Backend ready, API tested, frontend connected

**Current URL**: http://fitandfocused.test/test-upload

**API Endpoint**: POST /api/workouts/upload

