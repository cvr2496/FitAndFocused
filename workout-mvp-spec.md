# Workout Photo Logger - MVP Specification

## Executive Summary
A web application that digitizes handwritten workout logs via photo upload, using AI for OCR extraction, with user verification before database storage. Built for phone-free gym sessions while maintaining digital tracking benefits.

---

## WHY - Problem & Philosophy

### Core Problem
Existing workout apps require phone interaction during workouts, which is distracting and disrupts flow. Manual data entry is inflexible and doesn't capture contextual notes well.

### User Philosophy
- **Phone-free workouts**: The gym should be a one-hour phone-free zone
- **Flexibility**: Handwritten logs allow natural notation, clarifications, and context (e.g., "this gym uses kg not lbs", "machine broke on set 3")
- **Digital benefits**: Want analytics and tracking without in-gym phone usage

### Target User (Primary)
The builder themselves - personal use case validation first. A person who:
- Prefers paper logs during workouts
- Wants digital analytics and tracking
- Currently writes workouts in lined notebook
- Works out 3-4x per week
- Tracks: exercise name, sets, reps, weight, occasional notes

### Example Handwritten Log Format
```
12/28/25 Chest and Triceps
Bench Press: 10x60kg / 6x90kg / 8x70kg / 9x65kg
Incline Bench Press: 9x50kg / 8x75kg / 9x45kg
DB Chest Flies: 12x12kg / 10x12kg / 10x12kg
Cable Tricep Pulldown: 12x17kg / 15x17kg / 12x17kg
Cable Overhead Press: 8x17kg / 10x17kg / 10x17kg
Single Leaning Cable Pushback: 10x12.5lbs
```

**Key observations:**
- Informal notation: "10x60kg" means 10 reps at 60kg
- Variable formats: sometimes "reps×weight", sometimes listed with slashes
- Mixed units: mostly kg, occasionally lbs
- Date format: MM/DD/YY
- Readable handwriting, consistent structure

---

## WHAT - Scope & Features

### MVP Feature List (3-Day Timeline)

#### Day 1: Core Extraction Loop
1. Upload workout photo (React component)
2. AI extraction via Claude API with vision
3. Structured JSON response with confidence scores
4. Verification UI (editable form, inline editing)
5. Save to database on user confirmation

#### Day 2: Basic Views
1. Workout list page (chronological feed)
2. Workout detail page (exercises + sets table + photo)
3. Exercise history page (filter all sets by exercise name)
4. Basic stats (total workouts, weekly volume)

#### Day 3: Polish & AI Summary
1. AI-generated workout summary on detail page
2. Highlight low-confidence fields in verification UI
3. Improved UI polish
4. Deploy and share via exe.dev

### Out of Scope for MVP
- ❌ User authentication (using exe.dev built-in sharing)
- ❌ Progressive overload recommendations (needs historical data)
- ❌ Form critique or technique advice
- ❌ Workout plan generation
- ❌ Mobile app (web only)
- ❌ Multi-user support
- ❌ Exercise database with autocomplete (Phase 2)

### Future Phases (Post-MVP)
**Phase 2** (After 4+ weeks of data):
- Volume/programming analysis
- Progressive overload suggestions
- Workout frequency recommendations
- Exercise name normalization with fuzzy matching

**Phase 3** (Optimization):
- Exercise autocomplete from history
- Pre-printed notebook templates
- Export functionality
- Multi-user support

---

## HOW - Technical Architecture

### Tech Stack

**Infrastructure:**
- **Hosting**: exe.dev (VM with persistent disk, built-in HTTPS)
- **Sharing**: exe.dev's `share add-link` (no auth needed for prototype)

**Backend:**
- **Framework**: Laravel (PHP)
- **Database**: SQLite (on persistent disk)
- **Storage**: Local filesystem (`storage/app/uploads/`)
- **AI**: Anthropic Claude API (Claude 4.5 Sonnet)

**Frontend:**
- **Framework**: React
- **Integration**: Either Inertia.js or separate SPA + Laravel API
- **Styling**: TBD (Tailwind recommended)

### Database Schema

```sql
CREATE TABLE workouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL,
    title TEXT,
    photo_path TEXT,  -- e.g., 'uploads/2025-12-28-chest.jpg'
    notes TEXT,       -- workout-level notes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workout_id INTEGER NOT NULL,
    exercise_name TEXT NOT NULL,
    set_number INTEGER NOT NULL,  -- maintains order (1, 2, 3...)
    reps INTEGER,
    weight DECIMAL(6,2),
    unit TEXT,  -- 'kg' or 'lbs'
    notes TEXT, -- set-level notes (nullable)
    FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
);

CREATE INDEX idx_sets_workout ON sets(workout_id);
CREATE INDEX idx_sets_exercise ON sets(exercise_name);
```

**Design Decision: One row per set**
- Enables easier querying (e.g., "all sets where weight > 80kg")
- Standard SQL approach
- set_number maintains order within workout

### API Endpoints

```
POST   /api/workouts/upload
  - Accepts: multipart/form-data with 'photo' file
  - Returns: Extracted JSON structure for verification
  
POST   /api/workouts
  - Accepts: Verified workout data
  - Saves to database
  - Returns: Created workout with ID

GET    /api/workouts
  - Returns: List of all workouts (ordered by date DESC)
  
GET    /api/workouts/:id
  - Returns: Single workout with all sets and photo URL

GET    /api/workouts/:id/summary
  - Calls Claude API for AI-generated summary
  - Returns: Markdown summary

GET    /api/exercises/:name/history
  - Returns: All sets for given exercise name across all workouts

GET    /api/stats
  - Returns: Basic stats (total workouts, weekly volume, etc.)
```

### Claude API Prompts

#### Extraction Prompt (Vision)
```
Extract workout data from this handwritten log photo.

Return ONLY valid JSON with this exact structure:
{
  "date": "YYYY-MM-DD",
  "title": "Workout title (e.g., 'Chest and Triceps')",
  "exercises": [
    {
      "name": "Exercise name",
      "sets": [
        {
          "reps": 10,
          "weight": 70,
          "unit": "kg",
          "notes": null,
          "confidence": "high"
        }
      ]
    }
  ],
  "notes": "Any workout-level notes"
}

NORMALIZATION RULES:
- "10x70kg" = "70kg x 10" = "70kg 10 reps" → {reps: 10, weight: 70, unit: "kg"}
- "12.5lbs" = "12.5 lbs" = "12.5lb" → {weight: 12.5, unit: "lbs"}
- When multiple sets listed with slashes (e.g., "10x60kg / 6x90kg"), create separate set objects
- Preserve order of exercises and sets as written

CONFIDENCE SCORING:
- Mark "confidence": "low" for unclear/illegible fields
- Use null for values you cannot read
- Be conservative - if unsure, mark as low confidence

Do not include any markdown, explanations, or text outside the JSON structure.
```

#### Summary Prompt (Text)
```
Generate a concise workout summary based on this data:

Date: {date}
Title: {title}
Exercises: {exercise_list}
Total Sets: {total_sets}

Provide:
1. Brief overview (1-2 sentences)
2. Top sets for each exercise (heaviest weight or most reps)
3. Any notable patterns or callouts from notes

Format as markdown. Be encouraging but factual. Keep it under 150 words.
```

### User Flow

```
1. User completes workout on paper
2. User photographs notebook page (end of workout or later)
3. User uploads photo to web app
   ↓
4. Backend saves photo to disk
5. Backend calls Claude API (vision) with photo
6. Claude extracts structured workout data
   ↓
7. Frontend displays verification UI:
   - Editable form pre-filled with extracted data
   - Low-confidence fields highlighted in yellow/orange
   - Inline editing (click to edit, tab to next)
   - Original photo visible for reference
   ↓
8. User reviews/corrects (quick scan: ~30-60 seconds)
9. User clicks "Save Workout"
   ↓
10. Backend saves to database (workouts + sets tables)
11. Redirect to workout detail page
    ↓
12. Workout detail shows:
    - Photo
    - Exercises + sets in table format
    - AI-generated summary
    - Quick stats (total sets, top lifts)
    - Link to exercise history for each exercise
```

### Key Technical Decisions

**1. File Storage**
- Store on local filesystem: `storage/app/public/uploads/`
- Run `php artisan storage:link` to create symlink
- Access via `/storage/uploads/{filename}`
- No external S3/cloud storage needed (exe.dev has persistent disk)

**2. Image Handling**
- Accept JPEG/PNG uploads
- Optional: Convert to JPEG, compress to 1MB max
- Store original (for reference during verification)
- No need for thumbnails in MVP

**3. Exercise Name Handling**
- **MVP**: Store exactly as written (no normalization)
- **Rationale**: Premature optimization; user can search/filter with fuzzy matching later
- **Future**: After usage patterns emerge, add autocomplete from history

**4. Data Validation**
- Backend validates JSON structure from Claude
- Frontend validates user corrections before save
- Ensure: reps > 0, weight >= 0, unit in ['kg', 'lbs']
- Allow null values (incomplete data is OK)

**5. Error Handling**
- If Claude API fails: show error, allow manual entry
- If photo unreadable: flag to user, suggest retaking photo
- If database write fails: preserve extracted data, allow retry

### Performance Considerations

**For MVP (acceptable):**
- Photo upload: ~2-5 seconds
- Claude API extraction: ~3-8 seconds
- Verification time: ~30-60 seconds
- Total time to log workout: ~1-2 minutes

**If performance issues arise:**
- Add loading states/spinners
- Consider background job for Claude API call
- Cache AI summaries (regenerate only on data change)

### Deployment (exe.dev)

```bash
# On exe.dev VM
cd /var/www/workout-app

# Setup
composer install
php artisan migrate
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000

# Share publicly or with specific users
share set-public workout-app
# OR
share add-link workout-app
```

**Environment Variables:**
```
APP_ENV=local
DB_CONNECTION=sqlite
ANTHROPIC_API_KEY=sk-ant-...
```

---

## Success Metrics (Self-Validation)

**Week 1:**
- [ ] Successfully log 3 workouts via photo upload
- [ ] Verification takes <2 minutes per workout
- [ ] OCR accuracy >85% (need to manually correct <3 fields per workout)

**Week 2-4:**
- [ ] Continue using consistently (12+ workouts logged)
- [ ] Find value in exercise history view
- [ ] AI summaries feel useful/motivating
- [ ] Prefer this workflow over manual app entry

**Failure conditions (pivot/abandon):**
- Verification consistently takes >3 minutes (too tedious)
- OCR accuracy <70% (not worth the effort)
- Revert to paper-only or switch to direct app entry

---

## Open Questions / Decisions Needed

1. **Laravel + React integration**: Inertia.js or separate SPA?
2. **Styling**: Tailwind CSS? Bootstrap? Custom CSS?
3. **AI Summary scope**: Just summarize, or attempt basic analysis even with limited data?
4. **Date handling**: Auto-detect from photo, or user confirms/edits?
5. **Units conversion**: Should app convert lbs↔kg, or just store as-is?

---

## Context for Future LLMs

**This is a prototype built for personal validation.** Priority is speed to working product, not scalability or polish. The builder works out 3-4x/week and needs this functional within days to test the core hypothesis: "Is photo-based logging better than app-based logging for phone-free workouts?"

**Key constraints:**
- Single user (no multi-tenancy needed)
- Small data volume (~50-200 workouts/year)
- SQLite is sufficient
- No auth needed (exe.dev handles sharing)
- Handwriting is consistent and legible

**If helping extend this project, ask:**
1. How many workouts have been logged? (Determines if advanced analytics are possible)
2. What specific analytics question needs answering?
3. Is the core workflow (photo → verify → save) actually being used regularly?

Don't prematurely optimize or add features. Validate the core loop first.