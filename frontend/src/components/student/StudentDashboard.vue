<template>
  <div class="space-y-6">
    <p v-if="dataError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
      {{ dataError }}
      <button type="button" class="ml-2 font-bold underline" @click="loadStudentData">Retry</button>
    </p>
    <p v-if="isLoadingData" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600" role="status">Loading your academic records and services…</p>
    <!-- Student Profile Header Banner -->
    <div class="bg-gradient-to-r from-[#022c22] via-[#064e3b] to-[#09090b] rounded-2xl p-6 text-white shadow-2xl border border-emerald-800/40">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
          <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-400 via-teal-500 to-emerald-600 text-slate-950 font-black text-2xl flex items-center justify-center shadow-lg border-2 border-emerald-300/40">
            {{ userInitials }}
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-2xl font-black tracking-tight text-white">{{ formatDisplayName(student?.name || authState.user?.name) }}</h2>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wide shadow-sm"
                :class="studentTypeBadgeClass(student?.student_type)"
              >
                {{ formatStudentType(student?.student_type) }}
              </span>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wide"
                :class="student?.status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40'"
              >
                {{ student?.status || 'PENDING' }}
              </span>
            </div>
            <p class="text-sm text-slate-300 mt-1 flex flex-wrap items-center gap-3">
              <span><strong>ID:</strong> {{ student?.student_number || '2026-PENDING' }}</span>
              <span>•</span>
              <span><strong>Program:</strong> {{ student?.course_program || 'BS Information Technology' }}</span>
              <span>•</span>
              <span><strong>Year:</strong> Level {{ student?.year_level || 1 }}</span>
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="activeTab = 'enrollment'"
            class="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs rounded-xl shadow transition flex items-center gap-2"
          >
            Enrollment Application
          </button>
          <button
            @click="activeTab = 'documents'"
            class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-xs rounded-xl border border-white/20 transition flex items-center gap-2"
          >
            Request Official Documents
          </button>
        </div>
      </div>

      <!-- Quick Metrics Ribbon -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-emerald-800/30">
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Released Grades</span>
          <p class="text-xl font-bold text-white mt-0.5">{{ studentGrades.length }} Records</p>
        </div>
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Current GWA</span>
          <p class="text-xl font-bold text-emerald-300 mt-0.5">{{ computedGwa }}</p>
        </div>
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Clearance Status</span>
          <p class="text-xl font-bold mt-0.5" :class="clearance?.status === 'cleared' ? 'text-emerald-300' : 'text-amber-300'">
            {{ clearance?.status === 'cleared' ? '✓ CLEARED' : 'PENDING' }}
          </p>
        </div>
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Active Doc Requests</span>
          <p class="text-xl font-bold text-white mt-0.5">{{ activeDocRequestsCount }} Pending</p>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-1 overflow-x-auto">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === tab.id ? 'bg-[#064e3b] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>{{ tab.name }}</span>
        <span
          v-if="tab.badge"
          class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-400 text-slate-950"
        >
          {{ tab.badge }}
        </span>
      </button>
    </div>

    <!-- Transparent enrollment progress rail: visible before every service workspace. -->
    <div class="grid gap-3 rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:grid-cols-4">
      <div v-for="(stage, index) in enrollmentStages" :key="stage.name" class="flex items-center gap-3" :class="index < 3 ? 'sm:border-r sm:border-slate-100 sm:pr-3' : ''">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black" :class="index <= currentEnrollmentStage ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-400'">{{ index + 1 }}</span>
        <div><p class="text-xs font-bold text-slate-900">{{ stage.name }}</p><p class="text-[11px] text-slate-500">{{ stage.owner }}</p></div>
      </div>
    </div>

    <!-- TAB 1: OVERVIEW -->
    <div v-if="activeTab === 'overview'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Next Steps / Action Items Card -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span> Academic & Administrative Checklist
            </h3>
            <span class="text-xs text-slate-500 font-medium">AY 2026–2027 1st Term</span>
          </div>

          <div class="space-y-3">
            <div class="p-3.5 rounded-xl border flex items-center justify-between bg-slate-50 border-slate-200">
              <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold">1</span>
                <div>
                  <p class="text-xs font-bold text-slate-900">Enrollment Application Form</p>
                  <p class="text-[11px] text-slate-500">Student Category: {{ formatStudentType(student?.student_type) }}</p>
                </div>
              </div>
              <span
                class="text-[11px] font-bold px-2.5 py-1 rounded-full uppercase"
                :class="latestApplication?.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
              >
                {{ latestApplication?.status || 'NOT SUBMITTED' }}
              </span>
            </div>

            <div class="p-3.5 rounded-xl border flex items-center justify-between bg-slate-50 border-slate-200">
              <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold">2</span>
                <div>
                  <p class="text-xs font-bold text-slate-900">Department & Cashier Clearance</p>
                  <p class="text-[11px] text-slate-500">Required prior to official enrollment validation</p>
                </div>
              </div>
              <span
                class="text-[11px] font-bold px-2.5 py-1 rounded-full uppercase"
                :class="clearance?.status === 'cleared' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
              >
                {{ clearance?.status || 'PENDING' }}
              </span>
            </div>

            <div class="p-3.5 rounded-xl border flex items-center justify-between bg-slate-50 border-slate-200">
              <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs font-bold">3</span>
                <div>
                  <p class="text-xs font-bold text-slate-900">Subject Load Verification</p>
                  <p class="text-[11px] text-slate-500">{{ studentGrades.length }} courses enrolled</p>
                </div>
              </div>
              <span class="text-[11px] font-bold px-2.5 py-1 rounded-full uppercase bg-emerald-100 text-emerald-800">
                VERIFIED
              </span>
            </div>
          </div>
        </div>

        <!-- Student Category Advising Notice -->
        <div class="bg-gradient-to-br from-[#09090b] to-[#022c22] rounded-2xl p-6 text-white shadow-md border border-emerald-900/60 flex flex-col justify-between">
          <div>
            <div class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
              Category Specific Advising
            </div>
            <h4 class="text-base font-bold text-white mb-2">
              Enrollee Policy: {{ formatStudentType(student?.student_type) }}
            </h4>
            <p class="text-xs text-slate-300 leading-relaxed">
              {{ getStudentTypeAdvising(student?.student_type) }}
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-emerald-800/30 flex items-center justify-between text-xs">
            <span class="text-slate-400">Advising Office</span>
            <span class="font-semibold text-emerald-300">CCS Dean's Desk</span>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: ENROLLMENT WIZARD -->
    <div v-if="activeTab === 'enrollment'" class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
          <h3 class="text-lg font-bold text-slate-900">Enrollment Application System</h3>
          <p class="text-xs text-slate-500 mt-0.5">Submit or review your admission and registration application for the upcoming term.</p>
        </div>
        <div v-if="latestApplication" class="flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-500">Status:</span>
          <span
            class="px-3 py-1 rounded-full text-xs font-bold uppercase"
            :class="latestApplication.status === 'approved' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
          >
            {{ latestApplication.status }}
          </span>
        </div>
      </div>

      <!-- Application Status Evaluation Alert if already submitted -->
      <div v-if="latestApplication" class="p-4 rounded-xl border bg-slate-50 border-slate-200 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-slate-800 uppercase tracking-wide">Registrar Evaluation Notes</span>
          <span class="text-[11px] text-slate-500">Assigned Section: <strong>{{ latestApplication.assigned_section || 'TBA' }}</strong></span>
        </div>
        <p class="text-xs text-slate-600 bg-white p-3 rounded-lg border border-slate-200/80">
          {{ latestApplication.evaluation_notes || 'Application is currently under evaluation by the Registrar and Department Dean.' }}
        </p>
      </div>

      <!-- Application Form -->
      <form @submit.prevent="submitEnrollmentApp" class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Student Type Category -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Enrollee Category <span class="text-red-500">*</span>
            </label>
            <select
              v-model="enrollmentForm.student_type"
              required
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
            >
              <option value="new">Freshman / New Enrollee</option>
              <option value="transferee">Transferee from Other Institution</option>
              <option value="irregular">Irregular / Retake Student</option>
              <option value="old">Old / Continuing Regular Student</option>
              <option value="shiftee">Shiftee (Changing Degree Program)</option>
              <option value="returnee">Returnee (Returning from LOA)</option>
            </select>
          </div>

          <!-- Target Academic Program -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Degree Program <span class="text-red-500">*</span>
            </label>
            <select
              v-model="enrollmentForm.target_program"
              required
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
            >
              <option value="BS Information Technology">BS Information Technology (BSIT)</option>
              <option value="BS Computer Science">BS Computer Science (BSCS)</option>
              <option value="BS Information Systems">BS Information Systems (BSIS)</option>
              <option value="Associate in Computer Technology">Associate in Computer Technology (ACT)</option>
            </select>
          </div>

          <!-- Year Level -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Year Level <span class="text-red-500">*</span>
            </label>
            <select
              v-model="enrollmentForm.year_level"
              required
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
            >
              <option :value="1">1st Year</option>
              <option :value="2">2nd Year</option>
              <option :value="3">3rd Year</option>
              <option :value="4">4th Year</option>
            </select>
          </div>

          <!-- Previous School -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Previous School / Department <span class="text-red-500">*</span>
            </label>
            <input
              v-model="enrollmentForm.previous_school"
              type="text"
              required
              placeholder="e.g. Metro Science High School / College of Eng"
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
            />
          </div>

          <!-- Contact Number -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Contact Number <span class="text-red-500">*</span>
            </label>
            <input
              v-model="enrollmentForm.contact_number"
              type="text"
              required
              placeholder="+63 9XX XXX XXXX"
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
            />
          </div>

          <!-- Emergency Contact -->
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
              Emergency Contact & Relationship <span class="text-red-500">*</span>
            </label>
            <input
              v-model="enrollmentForm.emergency_contact"
              type="text"
              required
              placeholder="Parent/Guardian (+63 9XX XXX XXXX)"
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
            />
          </div>
        </div>

        <!-- Educational Background -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Educational Background & Application Statement <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="enrollmentForm.educational_background"
            rows="3"
            required
            placeholder="Outline your previous academic background, subjects completed, or reason for shifting/returning..."
            class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
          ></textarea>
        </div>

        <!-- Document Checklist Checklist -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
            Required Credentials Verification Checklist
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
            <label class="flex items-center gap-2.5 text-slate-700 cursor-pointer">
              <input type="checkbox" checked class="rounded text-blue-600 focus:ring-blue-500" />
              <span>Form 137 / Official Transcript of Records</span>
            </label>
            <label class="flex items-center gap-2.5 text-slate-700 cursor-pointer">
              <input type="checkbox" checked class="rounded text-blue-600 focus:ring-blue-500" />
              <span>Certificate of Good Moral Character</span>
            </label>
            <label class="flex items-center gap-2.5 text-slate-700 cursor-pointer">
              <input type="checkbox" checked class="rounded text-blue-600 focus:ring-blue-500" />
              <span>PSA Authenticated Birth Certificate</span>
            </label>
            <label class="flex items-center gap-2.5 text-slate-700 cursor-pointer">
              <input type="checkbox" checked class="rounded text-blue-600 focus:ring-blue-500" />
              <span>2x2 Recent Formal ID Photographs</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-6 py-2.5 rounded-xl bg-[#064e3b] hover:bg-[#022c22] text-white font-bold text-xs shadow-md transition disabled:opacity-50 flex items-center gap-2"
          >
            <span>{{ isSubmitting ? 'Submitting...' : 'Submit Application to Registrar' }}</span>
          </button>
        </div>
      </form>
    </div>

    <SectionWorkspace v-if="activeTab === 'enrollment'" mode="student" />

    <!-- TAB 3: CLEARANCE TRACKER -->
    <div v-if="activeTab === 'clearance'" class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
          <h3 class="text-lg font-bold text-slate-900">Student Clearance Workflow</h3>
          <p class="text-xs text-slate-500 mt-0.5">Track sign-offs across all university administrative and academic departments.</p>
        </div>
        <button
          @click="initiateClearance"
          class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs rounded-xl border border-emerald-300 transition"
        >
          🔄 Refresh / Re-submit Clearance
        </button>
      </div>

      <!-- Visual Step by Step Department Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Cashier -->
        <div class="p-4 rounded-xl border flex flex-col justify-between" :class="clearance?.cashier_status ? 'bg-emerald-50/70 border-emerald-300' : 'bg-amber-50/70 border-amber-300'">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase text-slate-900">1. Cashier & Finance</span>
              <span class="text-xs font-black" :class="clearance?.cashier_status ? 'text-emerald-700' : 'text-amber-700'">
                {{ clearance?.cashier_status ? '✓ CLEARED' : 'PENDING' }}
              </span>
            </div>
            <p class="text-xs text-slate-600 mt-2">{{ clearance?.cashier_remarks || 'Tuition fee assessment and financial holds.' }}</p>
          </div>
          <span class="text-[10px] text-slate-400 mt-4">Approver: Accounting Office</span>
        </div>

        <!-- Library -->
        <div class="p-4 rounded-xl border flex flex-col justify-between" :class="clearance?.library_status ? 'bg-emerald-50/70 border-emerald-300' : 'bg-amber-50/70 border-amber-300'">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase text-slate-900">2. University Library</span>
              <span class="text-xs font-black" :class="clearance?.library_status ? 'text-emerald-700' : 'text-amber-700'">
                {{ clearance?.library_status ? '✓ CLEARED' : 'PENDING' }}
              </span>
            </div>
            <p class="text-xs text-slate-600 mt-2">{{ clearance?.library_remarks || 'Book loans, overdue fines, and e-library access.' }}</p>
          </div>
          <span class="text-[10px] text-slate-400 mt-4">Approver: Chief Librarian</span>
        </div>

        <!-- Department Head -->
        <div class="p-4 rounded-xl border flex flex-col justify-between" :class="clearance?.dept_status ? 'bg-emerald-50/70 border-emerald-300' : 'bg-amber-50/70 border-amber-300'">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase text-slate-900">3. Department Head</span>
              <span class="text-xs font-black" :class="clearance?.dept_status ? 'text-emerald-700' : 'text-amber-700'">
                {{ clearance?.dept_status ? '✓ CLEARED' : 'PENDING' }}
              </span>
            </div>
            <p class="text-xs text-slate-600 mt-2">{{ clearance?.dept_remarks || 'Academic program requirements & laboratory clearance.' }}</p>
          </div>
          <span class="text-[10px] text-slate-400 mt-4">Approver: Department Chair</span>
        </div>

        <!-- Dean -->
        <div class="p-4 rounded-xl border flex flex-col justify-between" :class="clearance?.dean_status ? 'bg-emerald-50/70 border-emerald-300' : 'bg-amber-50/70 border-amber-300'">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase text-slate-900">4. College Dean</span>
              <span class="text-xs font-black" :class="clearance?.dean_status ? 'text-emerald-700' : 'text-amber-700'">
                {{ clearance?.dean_status ? '✓ CLEARED' : 'PENDING' }}
              </span>
            </div>
            <p class="text-xs text-slate-600 mt-2">{{ clearance?.dean_remarks || 'Academic standing and curriculum endorsement.' }}</p>
          </div>
          <span class="text-[10px] text-slate-400 mt-4">Approver: College Dean</span>
        </div>

        <!-- Guidance -->
        <div class="p-4 rounded-xl border flex flex-col justify-between" :class="clearance?.guidance_status ? 'bg-emerald-50/70 border-emerald-300' : 'bg-amber-50/70 border-amber-300'">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase text-slate-900">5. Guidance Counselor</span>
              <span class="text-xs font-black" :class="clearance?.guidance_status ? 'text-emerald-700' : 'text-amber-700'">
                {{ clearance?.guidance_status ? '✓ CLEARED' : 'PENDING' }}
              </span>
            </div>
            <p class="text-xs text-slate-600 mt-2">{{ clearance?.guidance_remarks || 'Exit interview and counseling profile.' }}</p>
          </div>
          <span class="text-[10px] text-slate-400 mt-4">Approver: Guidance Office</span>
        </div>

        <!-- Registrar -->
        <div class="p-4 rounded-xl border flex flex-col justify-between" :class="clearance?.registrar_status ? 'bg-emerald-50/70 border-emerald-300' : 'bg-amber-50/70 border-amber-300'">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase text-slate-900">6. University Registrar</span>
              <span class="text-xs font-black" :class="clearance?.registrar_status ? 'text-emerald-700' : 'text-amber-700'">
                {{ clearance?.registrar_status ? '✓ CLEARED' : 'PENDING' }}
              </span>
            </div>
            <p class="text-xs text-slate-600 mt-2">{{ clearance?.registrar_remarks || 'Final records sign-off and certificate issuance.' }}</p>
          </div>
          <span class="text-[10px] text-slate-400 mt-4">Approver: University Registrar</span>
        </div>
      </div>
    </div>

    <!-- TAB 4: GRADES -->
    <div v-if="activeTab === 'grades'" class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
          <h3 class="text-lg font-bold text-slate-900">Official Academic Records & Grades</h3>
          <p class="text-xs text-slate-500 mt-0.5">Computed GWA from released grades: <strong class="text-slate-900">{{ computedGwa }}</strong></p>
        </div>
        <div class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-800">
          {{ studentGrades.length ? 'Released academic records' : 'No released grades' }}
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-y border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3 px-4">Subject Code</th>
              <th class="py-3 px-4">Description</th>
              <th class="py-3 px-4 text-center">Units</th>
              <th class="py-3 px-4 text-center">Midterm</th>
              <th class="py-3 px-4 text-center">Final</th>
              <th class="py-3 px-4 text-center">Rating</th>
              <th class="py-3 px-4 text-center">Remarks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-if="!studentGrades.length">
              <td colspan="7" class="px-4 py-10 text-center">
                <p class="font-semibold text-slate-800">No released grades are available.</p>
                <p class="mt-1 text-sm text-slate-500">Grades appear here after you are enrolled in a section, faculty submit the course results, and the results are released.</p>
              </td>
            </tr>
            <tr v-for="grade in studentGrades" :key="grade.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4 font-bold text-emerald-950">{{ grade.course?.code || '—' }}</td>
              <td class="py-3.5 px-4 font-medium">{{ grade.course?.title || '—' }}</td>
              <td class="py-3.5 px-4 text-center font-semibold">{{ grade.course?.units ?? '—' }}</td>
              <td class="py-3.5 px-4 text-center font-bold">{{ grade.midterm_grade ?? '—' }}</td>
              <td class="py-3.5 px-4 text-center font-bold">{{ grade.final_grade ?? '—' }}</td>
              <td class="py-3.5 px-4 text-center font-extrabold text-emerald-900">{{ grade.grade ?? '—' }}</td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  :class="grade.remarks === 'Passed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ grade.remarks || '—' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 5: DOCUMENT REQUESTS -->
    <div v-if="activeTab === 'documents'" class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
          <h3 class="text-lg font-bold text-slate-900">Official Document Request Center</h3>
          <p class="text-xs text-slate-500 mt-0.5">Order official university credentials, transcripts, and certificates online.</p>
        </div>
        <button
          @click="showRequestModal = true"
          class="px-4 py-2 bg-[#064e3b] hover:bg-[#022c22] text-white font-bold text-xs rounded-xl shadow transition"
        >
          New Document Request
        </button>
      </div>

      <!-- Requests History Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-y border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3 px-4">Document Type</th>
              <th class="py-3 px-4">Purpose</th>
              <th class="py-3 px-4 text-center">Copies</th>
              <th class="py-3 px-4 text-center">Fee</th>
              <th class="py-3 px-4 text-center">Payment</th>
              <th class="py-3 px-4 text-center">Fulfillment Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="doc in studentDocuments" :key="doc.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ doc.document_type || doc.type }}</td>
              <td class="py-3.5 px-4 text-slate-600">{{ doc.purpose }}</td>
              <td class="py-3.5 px-4 text-center font-semibold">{{ doc.copies }}</td>
              <td class="py-3.5 px-4 text-center font-bold text-slate-900">PHP {{ Number(doc.fee).toFixed(2) }}</td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  :class="doc.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                >
                  {{ doc.payment_status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  :class="formatDocStatusClass(doc.status)"
                >
                  {{ formatDocStatus(doc.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Request Document Modal -->
    <Modal v-model="showRequestModal" title="Request Official Institutional Document" subtitle="Fees are payable at the Cashier counter or accredited online channels">
      <form @submit.prevent="submitDocumentRequest" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Select Document Type</label>
          <select
            v-model="docForm.document_type"
            required
            class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:ring-2 focus:ring-blue-600 focus:outline-none"
          >
            <option value="Transcript of Records (TOR)">Transcript of Records (TOR) — PHP 150.00 / copy</option>
            <option value="Certificate of Good Moral Character">Certificate of Good Moral Character — PHP 150.00</option>
            <option value="Certificate of Grades (COG)">Certificate of Grades (COG) — PHP 100.00</option>
            <option value="Certificate of Enrollment">Certificate of Enrollment — PHP 100.00</option>
            <option value="Honorable Dismissal">Honorable Dismissal — PHP 250.00</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Number of Copies</label>
            <input
              v-model.number="docForm.copies"
              type="number"
              min="1"
              max="10"
              required
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:ring-2 focus:ring-blue-600 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Estimated Fee</label>
            <div class="text-xs bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 font-bold text-slate-800">
              PHP {{ computedDocFee.toFixed(2) }}
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Purpose of Request</label>
          <input
            v-model="docForm.purpose"
            type="text"
            required
            placeholder="e.g. Employment, Scholarship, Transfer, Licensure"
            class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-medium text-slate-800 focus:ring-2 focus:ring-blue-600 focus:outline-none"
          />
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showRequestModal = false"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-5 py-2 text-xs font-bold bg-[#064e3b] hover:bg-[#022c22] text-white rounded-xl shadow transition"
          >
            Confirm Request
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { authState, formatDisplayName, portalApi } from '../../services/api';
import { activeSidebarSection } from '../../services/navigation';
import Modal from '../Modal.vue';
import SectionWorkspace from '../sections/SectionWorkspace.vue';
import type { StudentType, StudentApplication, Clearance, Enrollment, DocumentRequest } from '../../types';

type TabType = 'overview' | 'enrollment' | 'clearance' | 'grades' | 'documents';
const activeTab = ref<TabType>('overview');
const showRequestModal = ref(false);
const isSubmitting = ref(false);
const isLoadingData = ref(false);
const dataError = ref('');

const studentGrades = ref<Enrollment[]>([]);
const studentDocuments = ref<DocumentRequest[]>([]);
const latestApplication = ref<StudentApplication | null>(null);
const clearance = ref<Clearance | null>(null);

const student = computed(() => authState.user?.student);

const userInitials = computed(() => {
  const name = formatDisplayName(student.value?.name || authState.user?.name) || 'ST';
  return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
});

const tabs = computed<{ id: TabType; name: string; badge?: string }[]>(() => [
  { id: 'overview', name: 'Overview' },
  { id: 'enrollment', name: 'Enrollment Application', badge: latestApplication.value?.status === 'pending' ? 'Pending' : '' },
  { id: 'clearance', name: 'Clearance Tracker', badge: clearance.value?.status === 'pending' ? 'Action' : '' },
  { id: 'grades', name: 'Academic Grades' },
  { id: 'documents', name: 'Document Requests' },
]);

const enrollmentForm = ref({
  student_type: 'new' as StudentType,
  target_program: 'BS Information Technology',
  year_level: 1,
  previous_school: '',
  contact_number: '',
  emergency_contact: '',
  educational_background: '',
});

const docForm = ref({
  document_type: 'Transcript of Records (TOR)',
  copies: 1,
  purpose: '',
});

const computedDocFee = computed(() => {
  const rates: Record<string, number> = {
    'Transcript of Records (TOR)': 150,
    'Certificate of Good Moral Character': 150,
    'Certificate of Grades (COG)': 100,
    'Certificate of Enrollment': 100,
    'Honorable Dismissal': 250,
  };
  return (rates[docForm.value.document_type] || 150) * (docForm.value.copies || 1);
});

const computedGwa = computed(() => {
  const validGrades = studentGrades.value
    .map((grade) => Number(grade.grade))
    .filter((grade) => Number.isFinite(grade) && grade > 0);
  if (!validGrades.length) return '—';
  const sum = validGrades.reduce((acc, curr) => acc + curr, 0);
  return (sum / validGrades.length).toFixed(2);
});

const activeDocRequestsCount = computed(() => {
  return studentDocuments.value.filter((d) => d.status === 'submitted' || d.status === 'processing').length;
});

const enrollmentStages = computed(() => [
  { name: 'Application submitted', owner: 'Student' },
  { name: 'Credential review', owner: 'Registrar' },
  { name: 'Academic evaluation', owner: 'Department / Dean' },
  { name: 'Enrollment confirmed', owner: 'Cashier & Registrar' },
]);

const currentEnrollmentStage = computed(() => {
  if (latestApplication.value?.status === 'approved' && clearance.value?.status === 'cleared') return 3;
  if (latestApplication.value?.status === 'approved') return 2;
  if (latestApplication.value?.status === 'pending') return 1;
  return latestApplication.value?.status === 'rejected' ? 1 : -1;
});

async function loadStudentData() {
  if (!student.value) return;
  isLoadingData.value = true;
  dataError.value = '';

  enrollmentForm.value.student_type = student.value.student_type;
  enrollmentForm.value.target_program = student.value.course_program;
  enrollmentForm.value.year_level = student.value.year_level;

  try {
    const [grades, docs, apps, clearances] = await Promise.all([
      portalApi.getGrades(student.value.id),
      portalApi.getDocuments({ student_id: student.value.id }),
      portalApi.getApplications(),
      portalApi.getClearances({ student_id: student.value.id }),
    ]);

    studentGrades.value = grades;
    studentDocuments.value = docs;
    latestApplication.value = apps.find((a) => a.user_id === authState.user?.id) || null;
    clearance.value = clearances[0] || student.value.clearance || null;
  } catch (e) {
    console.error('Error loading student data:', e);
    dataError.value = e instanceof Error ? e.message : 'Your portal information could not be loaded. Please try again.';
  } finally {
    isLoadingData.value = false;
  }
}

async function submitEnrollmentApp() {
  if (!authState.user) return;
  isSubmitting.value = true;
  try {
    const res = await portalApi.submitApplication({
      user_id: authState.user.id,
      ...enrollmentForm.value,
    });
    latestApplication.value = res;
    activeTab.value = 'overview';
  } catch (err) {
    console.error(err);
  } finally {
    isSubmitting.value = false;
  }
}

async function initiateClearance() {
  if (!student.value) return;
  try {
    const res = await portalApi.requestClearance(student.value.id);
    clearance.value = res;
  } catch (e) {
    console.error(e);
  }
}

async function submitDocumentRequest() {
  if (!student.value) return;
  try {
    const res = await portalApi.requestDocument({
      student_id: student.value.id,
      ...docForm.value,
    });
    studentDocuments.value.unshift(res);
    showRequestModal.value = false;
    docForm.value.purpose = '';
  } catch (e) {
    console.error(e);
  }
}

function formatStudentType(type?: string) {
  switch (type) {
    case 'new': return 'Freshman / New Enrollee';
    case 'transferee': return 'Transferee';
    case 'irregular': return 'Irregular Student';
    case 'old': return 'Old / Continuing';
    case 'shiftee': return 'Shiftee';
    case 'returnee': return 'Returnee';
    default: return type || 'Student';
  }
}

function studentTypeBadgeClass(type?: string) {
  switch (type) {
    case 'new': return 'bg-emerald-500 text-white';
    case 'transferee': return 'bg-purple-600 text-white';
    case 'irregular': return 'bg-amber-500 text-slate-950';
    case 'old': return 'bg-blue-600 text-white';
    case 'shiftee': return 'bg-pink-600 text-white';
    case 'returnee': return 'bg-orange-600 text-white';
    default: return 'bg-slate-700 text-white';
  }
}

function getStudentTypeAdvising(type?: string) {
  switch (type) {
    case 'new': return 'As a freshman, you are assigned a block section. Please complete admission credential submission at the Registrar.';
    case 'transferee': return 'Your credited subjects are being evaluated by the Dean. Submit certified course descriptions from your previous college.';
    case 'irregular': return 'Curriculum advising is mandatory to balance prerequisite subjects with available schedule slots.';
    case 'shiftee': return 'Department chair shifting approval is required. Complete credit mapping to prevent redundant subjects.';
    case 'returnee': return 'Returnee re-admission clearance is required from the Guidance Office and College Dean.';
    default: return 'Ensure your semester clearance is settled before enrollment validation.';
  }
}

function formatDocStatus(status: string) {
  switch (status) {
    case 'submitted': return 'Submitted / Awaiting Payment';
    case 'processing': return 'Processing';
    case 'ready_for_pickup': return 'Ready for Pick-up';
    case 'released': return 'Released';
    default: return status;
  }
}

function formatDocStatusClass(status: string) {
  switch (status) {
    case 'submitted': return 'bg-amber-100 text-amber-800';
    case 'processing': return 'bg-blue-100 text-blue-800';
    case 'ready_for_pickup': return 'bg-purple-100 text-purple-800';
    case 'released': return 'bg-emerald-100 text-emerald-800';
    default: return 'bg-slate-100 text-slate-800';
  }
}

watch(() => authState.user, () => {
  loadStudentData();
});

watch(activeSidebarSection, (section) => {
  if (['overview', 'enrollment', 'clearance', 'grades', 'documents'].includes(section)) {
    activeTab.value = section as TabType;
  }
});

watch(activeTab, (tab) => {
  if (activeSidebarSection.value !== tab) {
    activeSidebarSection.value = tab;
  }
});

onMounted(() => {
  if (['overview', 'enrollment', 'clearance', 'grades', 'documents'].includes(activeSidebarSection.value)) {
    activeTab.value = activeSidebarSection.value as TabType;
  }
  loadStudentData();
});
</script>
