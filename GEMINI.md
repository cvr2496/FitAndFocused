# FitAndFocused - Gemini AI Assistant Guide

## Project Overview
FitAndFocused is a Laravel/React workout logging application that allows users to track workouts via photo upload or manual text entry. The app uses Anthropic's Claude API for OCR and workout data extraction.

## Tech Stack
- **Backend**: Laravel 12, PHP 8.2
- **Frontend**: React 19, Inertia.js 2, TypeScript, TailwindCSS 4
- **Database**: SQLite (local), MySQL (production)
- **AI Integration**: Anthropic Claude API (claude-3-5-sonnet-20241022)
- **Testing**: PHPUnit/Pest, Playwright

## Essential Commands

### Development
```bash
composer run dev          # Start all services (server, queue, logs, vite)
npm run dev              # Vite dev server only
php artisan serve        # Laravel server only
```

### Testing & Quality
```bash
composer test            # Run all PHPUnit/Pest tests
composer run analyse     # PHPStan static analysis
npm run lint             # ESLint with auto-fix
npm run types            # TypeScript type checking
```

### Database
```bash
php artisan migrate:fresh --seed    # Reset DB with demo data
php artisan db:seed --class=DemoUserSeeder  # Add demo workouts
```

## Key Architecture Patterns

### Service Layer
- **AnthropicService** (`app/Services/AnthropicService.php`): Handles Claude API calls
  - `extractWorkoutData($imagePath)`: Photo-based extraction
  - `extractFromText($text)`: Manual text extraction
  - Mock mode automatically enabled in local/testing when API key is missing
  - **IMPORTANT**: Always check `$this->mockMode` property before making real API calls

### Controllers
- **WorkoutUploadController**: Handles both photo and manual workout uploads
- **WorkoutController**: Display and CRUD operations
- **HomeController**: Dashboard with AI-powered workout recommendations

### Models
- **Workout**: Core model with PHPDoc annotations for PHPStan
  - **CRITICAL**: Must include `@property` declarations for all attributes
  - Custom content stored in JSON `custom_content` column
  - Type field: 'strength' | 'crossfit' | 'cardio' | 'other'

### Frontend (Inertia.js + React)
- Type definitions in `resources/js/types/workout.ts`
- Forms use `useForm<Workout>()` hook from Inertia
- **IMPORTANT**: `useForm` requires types to satisfy `FormDataType` constraints
  - Cannot use `unknown` or `any` for nested objects
  - Use explicit type unions or `Record<string, primitives>`

## Code Style & Conventions

### PHP
- Use PHPDoc `@property` annotations on all Eloquent models
- Type hint all method parameters and return types
- Run PHPStan before committing (`composer run analyse`)
- Follow PSR-12 coding standards

### TypeScript/React
- Use TypeScript strict mode
- Prefer type inference where obvious, explicit types for public interfaces
- **NO explicit `any` types** (use eslint-disable only when necessary with justification)
- Components use functional style with hooks
- Import destructuring: `import { foo } from 'bar'`

### Git Workflow
- Commit frequently in logical, revertible chunks
- Use conventional commits: `feat:`, `fix:`, `refactor:`, `test:`, `docs:`
- Always run linting and tests before committing
- Example: `feat(ui): Implement flexible workout logging UI`

## Common Gotchas

### Mock Mode Behavior
- `AnthropicService` enters mock mode if `ANTHROPIC_API_KEY` is empty in local/testing
- Mock responses are hardcoded in the service (see `extractFromText` and `extractWorkoutData`)
- To use real API: Set valid `ANTHROPIC_API_KEY` in `.env`

### Inertia Form Type Constraints
- `useForm<T>` requires `T extends FormDataType<T>`
- This excludes functions, symbols, and infinitely recursive types
- For flexible JSON: Use finite-depth `Record` types or explicit unions
- **NEVER use infinitely recursive type definitions** (causes TS2589 errors)

### Database Dates
- Laravel casts `date` fields to Carbon instances
- PHPStan needs `@property \Illuminate\Support\Carbon $date` annotations
- Always call `->format('Y-m-d')` when serializing for frontend

### Frontend Build
- Vite manifest must exist before running tests
- If tests fail with "Vite manifest not found": Run `npm run build`

## Testing Philosophy
- **Feature tests** for user-facing workflows (login, upload, verify, save)
- **Unit tests** for isolated service logic (minimal due to API dependencies)
- **Static analysis** (PHPStan, TypeScript) catches most type errors
- **Linting** enforces consistent code style

## File Structure (Key Paths)
```
app/
├── Http/Controllers/
│   ├── WorkoutController.php
│   ├── WorkoutUploadController.php
│   └── HomeController.php
├── Models/
│   ├── Workout.php
│   └── Set.php
├── Services/
│   └── AnthropicService.php
└── Features/AiCoach/
    └── AiCoach.php

resources/js/
├── types/workout.ts              # Core TypeScript definitions
├── pages/workouts/
│   ├── upload.tsx               # Photo + Manual entry tabs
│   ├── verify.tsx               # Edit extracted workout data
│   └── show.tsx                 # Display single workout

tests/Feature/
├── Controllers/
└── Workouts/

database/migrations/
└── 2026_01_20_215226_add_flexible_data_to_workouts_table.php
```

## Workflow Best Practices
1. **Plan before executing**: Create implementation plans for multi-file changes using artifacts
2. **Use task boundaries**: Break work into clear phases (Planning → Execution → Verification)
3. **Commit incrementally**: Organize commits by layer (database, services, controllers, frontend, cleanup)
4. **Verify as you go**: Run tests/linting after each logical chunk
5. **Use mock mode**: Test UI flows without burning API credits
6. **Request feedback**: Use `notify_user` with artifact paths when seeking approval

## Environment Variables
```bash
ANTHROPIC_API_KEY=       # Claude API key (optional in local, enables real extraction)
DB_CONNECTION=sqlite     # Use SQLite for local development
```

## Artifact Guidelines
When creating artifacts for user review:
- **Implementation Plans**: Document proposed changes, breaking changes, verification steps
- **Task Lists**: Granular checklist items marked as `[ ]`, `[/]` (in-progress), or `[x]` (done)
- **Walkthroughs**: Post-completion summary with screenshots/recordings of verification

## Current Focus Areas
- Flexible workout logging (supports CrossFit, Cardio, Strength)
- Manual text entry + Photo upload workflows
- AI-powered workout recommendations (experimental)
- Mock mode for development without API dependency
