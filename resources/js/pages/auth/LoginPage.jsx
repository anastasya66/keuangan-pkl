import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuth from '../../hooks/useAuth';

export function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    email: '',
    password: '',
  });

  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [fieldErrors, setFieldErrors] = useState({});
  const [toastNotice, setToastNotice] = useState('');

  const showQuickToast = (msg) => {
    setToastNotice(msg);
    setTimeout(() => setToastNotice(''), 3000);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    setFieldErrors((prev) => ({ ...prev, [name]: null }));
    setErrorMessage('');
  };

  const fillCredentials = (email, password, roleLabel) => {
    setFormData({ email, password });
    setFieldErrors({});
    setErrorMessage('');
    if (roleLabel) {
      showQuickToast(`Akun ${roleLabel} dimuat: ${email}`);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage('');
    setFieldErrors({});

    const errors = {};
    if (!formData.email.trim()) errors.email = 'Alamat email wajib diisi.';
    if (!formData.password) errors.password = 'Kata sandi wajib diisi.';

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    setIsSubmitting(true);
    const result = await login(formData.email.trim(), formData.password);
    setIsSubmitting(false);

    if (result.success) {
      if (result.user?.role === 'admin') {
        navigate('/admin/dashboard', { replace: true });
      } else {
        navigate('/staff/dashboard', { replace: true });
      }
    } else {
      setErrorMessage(result.message || 'Login gagal. Periksa kembali email dan password Anda.');
      if (result.errors) {
        setFieldErrors(result.errors);
      }
    }
  };

  return (
    <div className="min-h-screen lg:h-screen lg:max-h-screen bg-[#F2FAFB] text-[#16323B] flex flex-col justify-between py-3 sm:py-4 lg:py-3 xl:py-5 px-4 sm:px-8 md:px-12 relative overflow-y-auto lg:overflow-hidden select-none font-sans">
      
      {/* Toast Notification for quick demo selection */}
      {toastNotice && (
        <div className="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-[#16323B] text-white text-xs font-semibold px-4 py-2 rounded-full shadow-lg shadow-[#16323B]/20 flex items-center gap-2 animate-toast">
          <span className="w-2 h-2 rounded-full bg-[#27C5E5] animate-pulse" />
          <span>{toastNotice}</span>
        </div>
      )}

      {/* =========================================================================
          TOP NAVIGATION BAR (Responsive on all laptop & mobile screens)
          ========================================================================= */}
      <header className="max-w-7xl w-full mx-auto flex items-center justify-between z-20 shrink-0 pt-0.5">
        {/* Brand Left */}
        <div className="flex items-center gap-3">
          <img
            src="/images/fintoko-mark.png"
            alt="FinToko"
            className="w-9 h-9 sm:w-10 sm:h-10 object-contain shrink-0"
          />
          <div className="flex flex-col">
            <span className="text-xl sm:text-2xl font-black tracking-tight text-[#10262E] leading-tight">
              FinToko
            </span>
            {/* Characteristic Razor Underline */}
            <div className="w-10 sm:w-14 h-[1.8px] bg-[#16323B] mt-0.5" />
            <a
              href="mailto:support@fintoko.id"
              className="hidden sm:flex text-[11px] text-[#577782] hover:text-[#10262E] font-medium mt-1 items-center gap-1 transition-colors"
            >
              <span>sales@fintoko.id</span>
              <span>&rarr;</span>
            </a>
          </div>
        </div>

        {/* Actions Right */}
        <div className="flex items-center gap-2.5 sm:gap-4 md:gap-6">
          {/* Globe Icon */}
          <button
            type="button"
            className="hidden sm:inline-flex text-[#16323B] hover:text-[#27C5E5] transition-colors p-1"
            title="Bahasa"
          >
            <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6">
              <circle cx="12" cy="12" r="10" />
              <line x1="2" y1="12" x2="22" y2="12" />
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
            </svg>
          </button>

          {/* Sign Up Link */}
          <button
            type="button"
            onClick={() => fillCredentials('staff@fintoko.test', 'password', 'Staff')}
            className="text-xs font-semibold text-[#16323B] hover:text-[#27C5E5] transition-colors cursor-pointer"
          >
            Sign up
          </button>

          {/* Request Demo Button (Fills Admin Demo) */}
          <button
            type="button"
            onClick={() => fillCredentials('admin@fintoko.test', 'password', 'Admin')}
            className="px-3 sm:px-4 py-1.5 sm:py-2 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-semibold text-xs shadow-md shadow-[#27C5E5]/25 transition-all cursor-pointer btn-press"
          >
            Request Demo
          </button>
        </div>
      </header>

      {/* =========================================================================
          MAIN CANVAS: Symmetrical 3-Column Composition (Scales perfectly on laptop)
          ========================================================================= */}
      <main className="max-w-7xl w-full mx-auto my-auto py-2 xl:py-4 relative z-10 flex-1 flex items-center justify-center">
        
        {/* Continuous Horizontal Floor Line across the entire width (aligned with pedestals) */}
        <div className="absolute left-0 right-0 bottom-4 lg:bottom-7 xl:bottom-11 h-[1.5px] bg-[#16323B] pointer-events-none hidden lg:block z-0" />

        <div className="w-full grid grid-cols-1 lg:grid-cols-12 gap-2 xl:gap-4 items-center">
          
          {/* =====================================================================
              LEFT ARTWORK: Dotted Pillar + Growth Arrow Box + Floating Speech Box
              ===================================================================== */}
          <div className="hidden lg:flex lg:col-span-3 xl:col-span-3 justify-center items-end h-[340px] xl:h-[390px] 2xl:h-[430px] relative pb-6 xl:pb-10">
            <svg
              className="w-full h-full max-h-[320px] xl:max-h-[370px] 2xl:max-h-[410px] overflow-visible"
              viewBox="0 0 240 360"
              fill="none"
              strokeLinecap="round"
              strokeLinejoin="round"
            >
              {/* Curly Wire Doodle in the sky with dot */}
              <path
                d="M 20,40 Q 45,15 65,40 T 110,25 T 150,35"
                stroke="#16323B"
                strokeWidth="1.5"
              />
              <circle cx="158" cy="35" r="2.5" fill="#16323B" />

              {/* Floating Speech / Note Box */}
              <g transform="translate(15, 75)">
                {/* Speech tail */}
                <path d="M 25,60 L 20,72 L 36,60 Z" fill="#FFFFFF" stroke="#16323B" strokeWidth="1.5" />
                {/* Main bubble box */}
                <rect x="0" y="0" width="110" height="60" rx="10" fill="#FFFFFF" stroke="#16323B" strokeWidth="1.5" />
                {/* Text lines */}
                <line x1="18" y1="24" x2="88" y2="24" stroke="#CBD5E1" strokeWidth="2.5" strokeLinecap="round" />
                <line x1="18" y1="36" x2="62" y2="36" stroke="#CBD5E1" strokeWidth="2.5" strokeLinecap="round" />
              </g>

              {/* Small accent dot near speech box */}
              <circle cx="140" cy="115" r="3" fill="#16323B" />

              {/* Wavy squiggle above growth box */}
              <path
                d="M 140,205 Q 160,185 180,210 T 215,200"
                stroke="#16323B"
                strokeWidth="1.5"
              />

              {/* TALL DOTTED PILLAR (Icy Blue #A5E2EE with black dots) */}
              <g transform="translate(18, 160)">
                <rect
                  x="0"
                  y="0"
                  width="85"
                  height="180"
                  rx="14"
                  fill="#A5E2EE"
                  stroke="#16323B"
                  strokeWidth="1.5"
                />
                {/* Polka Dots Grid: 4 cols x 7 rows */}
                {[...Array(7)].map((_, r) =>
                  [...Array(4)].map((__, c) => (
                    <circle
                      key={`dot-${r}-${c}`}
                      cx={16 + c * 18}
                      cy={20 + r * 22}
                      r="2.5"
                      fill="#16323B"
                    />
                  ))
                )}
              </g>

              {/* WHITE GROWTH INDICATOR BOX (Right next to dotted pillar) */}
              <g transform="translate(120, 220)">
                <rect
                  x="0"
                  y="0"
                  width="85"
                  height="120"
                  rx="4"
                  fill="#FFFFFF"
                  stroke="#16323B"
                  strokeWidth="1.5"
                />
                {/* Upward curved arrow */}
                <path
                  d="M 28,82 C 28,52 42,36 62,32"
                  stroke="#16323B"
                  strokeWidth="2.2"
                />
                <polyline
                  points="50,30 64,32 60,46"
                  stroke="#16323B"
                  strokeWidth="2.2"
                />
              </g>
            </svg>
          </div>

          {/* =====================================================================
              CENTER LOGIN CARD (Pure White rounded-[28px]/[32px] card)
              ===================================================================== */}
          <div className="lg:col-span-6 xl:col-span-6 flex justify-center z-20 w-full px-2">
            <div className="w-full max-w-[400px] xl:max-w-[425px] bg-white border border-[#E0EFF2] rounded-2xl sm:rounded-3xl lg:rounded-[28px] xl:rounded-[32px] p-5 sm:p-7 lg:p-6 xl:p-8 shadow-xl shadow-[#16323B]/8 backdrop-blur animate-modal-pop">
              
              {/* Header Title & Subtitle */}
              <div className="text-center mb-4 sm:mb-5 flex flex-col items-center">
                <img
                  src="/images/fintoko-mark.png"
                  alt="FinToko"
                  className="w-12 h-12 object-contain mb-2"
                />
                <h1 className="text-xl sm:text-2xl xl:text-[26px] font-black text-[#10262E] tracking-tight">
                  Agent Login
                </h1>
                <p className="text-xs text-[#577782] mt-1 sm:mt-1.5 leading-relaxed">
                  Hey, Enter your details to get sign in<br />to your account
                </p>
              </div>

              {/* Error Alert */}
              {errorMessage && (
                <div className="mb-3 p-2.5 sm:p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold animate-toast flex items-center gap-2">
                  <span>⚠️</span>
                  <span className="flex-1">{errorMessage}</span>
                </div>
              )}

              {/* Form Body */}
              <form onSubmit={handleSubmit} className="space-y-3 sm:space-y-3.5 xl:space-y-4">
                {/* Field 1: Enter Email / Phone No */}
                <div>
                  <div className="relative">
                    <input
                      type="text"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      placeholder="Enter Email / Phone No"
                      autoComplete="email"
                      required
                      className={`w-full px-3.5 sm:px-4 py-2.5 sm:py-3 xl:py-3.5 pr-10 sm:pr-11 rounded-xl bg-white border text-xs sm:text-sm text-[#16323B] placeholder-[#8EA6AF] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#27C5E5]/25 transition-all input-focus-glow ${
                        fieldErrors.email
                          ? 'border-rose-400'
                          : 'border-[#E2ECEF] focus:border-[#27C5E5]'
                      }`}
                    />
                    {/* Circle icon as shown in Razor reference */}
                    <div className="absolute inset-y-0 right-0 pr-3 sm:pr-4 flex items-center pointer-events-none">
                      <div className="w-3.5 sm:w-4 h-3.5 sm:h-4 rounded-full border-[1.8px] border-[#9FCAD4]" />
                    </div>
                  </div>
                  {fieldErrors.email && (
                    <p className="text-[11px] text-rose-500 mt-1 font-medium">{fieldErrors.email}</p>
                  )}
                </div>

                {/* Field 2: Passcode with Hide/Show toggle */}
                <div>
                  <div className="relative">
                    <input
                      type={showPassword ? 'text' : 'password'}
                      name="password"
                      value={formData.password}
                      onChange={handleChange}
                      placeholder="Passcode"
                      autoComplete="current-password"
                      required
                      className={`w-full px-3.5 sm:px-4 pr-14 sm:pr-16 py-2.5 sm:py-3 xl:py-3.5 rounded-xl bg-white border text-xs sm:text-sm text-[#16323B] placeholder-[#8EA6AF] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#27C5E5]/25 transition-all input-focus-glow ${
                        fieldErrors.password
                          ? 'border-rose-400'
                          : 'border-[#E2ECEF] focus:border-[#27C5E5]'
                      }`}
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute inset-y-0 right-0 pr-3 sm:pr-4 flex items-center text-[11px] font-semibold text-[#577782] hover:text-[#10262E] cursor-pointer"
                    >
                      {showPassword ? 'Hide' : 'Show'}
                    </button>
                  </div>
                  {fieldErrors.password && (
                    <p className="text-[11px] text-rose-500 mt-1 font-medium">{fieldErrors.password}</p>
                  )}
                </div>

                {/* Having trouble in sign in? */}
                <div className="text-left pt-0.5">
                  <button
                    type="button"
                    onClick={() => fillCredentials('admin@fintoko.test', 'password', 'Admin')}
                    className="text-[11px] text-[#577782] hover:text-[#10262E] cursor-pointer font-medium transition-colors"
                  >
                    Having trouble in sign in?
                  </button>
                </div>

                {/* Submit Button */}
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="w-full mt-1.5 py-3 sm:py-3.5 rounded-xl bg-[#27C5E5] hover:bg-[#38BFE0] text-white font-bold text-xs sm:text-sm shadow-md shadow-[#27C5E5]/25 transition-all cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 btn-press"
                >
                  {isSubmitting ? (
                    <>
                      <span className="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                      <span>Memproses...</span>
                    </>
                  ) : (
                    <span>Sign in</span>
                  )}
                </button>
              </form>

              {/* Bottom Card Link */}
              <div className="mt-4 sm:mt-5 text-center text-[11px] text-[#577782]">
                <span>Don't have an account? </span>
                <button
                  type="button"
                  onClick={() => fillCredentials('admin@fintoko.test', 'password', 'Admin')}
                  className="font-bold text-[#10262E] hover:text-[#27C5E5] transition-colors cursor-pointer"
                >
                  Request Now
                </button>
              </div>
            </div>
          </div>

          {/* =====================================================================
              RIGHT ARTWORK: Woman sitting on white cube with laptop + Dotted Pillar
              ===================================================================== */}
          <div className="hidden lg:flex lg:col-span-3 xl:col-span-3 justify-center items-end h-[340px] xl:h-[390px] 2xl:h-[430px] relative pb-6 xl:pb-10">
            <svg
              className="w-full h-full max-h-[320px] xl:max-h-[370px] 2xl:max-h-[410px] overflow-visible"
              viewBox="0 0 280 360"
              fill="none"
              strokeLinecap="round"
              strokeLinejoin="round"
            >
              {/* Curly Wire Doodle in the sky with dot */}
              <path
                d="M 120,40 Q 145,15 165,38 T 205,28 T 240,36"
                stroke="#16323B"
                strokeWidth="1.5"
              />
              <circle cx="248" cy="36" r="2.5" fill="#16323B" />

              {/* Floating Note / Dialog Box */}
              <g transform="translate(145, 80)">
                <rect x="0" y="0" width="85" height="52" rx="8" fill="#FFFFFF" stroke="#16323B" strokeWidth="1.5" />
                <line x1="14" y1="20" x2="68" y2="20" stroke="#CBD5E1" strokeWidth="2" strokeLinecap="round" />
                <line x1="14" y1="32" x2="50" y2="32" stroke="#CBD5E1" strokeWidth="2" strokeLinecap="round" />
              </g>

              {/* Small accent dot */}
              <circle cx="250" cy="115" r="3" fill="#16323B" />

              {/* WHITE PEDESTAL CUBE (Girl sits on this) */}
              <g transform="translate(68, 195)">
                <rect
                  x="0"
                  y="0"
                  width="100"
                  height="145"
                  fill="#FFFFFF"
                  stroke="#16323B"
                  strokeWidth="1.5"
                />
              </g>

              {/* SHORTER DOTTED PILLAR (Right next to the white cube) */}
              <g transform="translate(182, 225)">
                <rect
                  x="0"
                  y="0"
                  width="75"
                  height="115"
                  rx="12"
                  fill="#A5E2EE"
                  stroke="#16323B"
                  strokeWidth="1.5"
                />
                {/* Polka Dots Grid: 3 cols x 5 rows */}
                {[...Array(5)].map((_, r) =>
                  [...Array(3)].map((__, c) => (
                    <circle
                      key={`rdot-${r}-${c}`}
                      cx={16 + c * 21}
                      cy={18 + r * 20}
                      r="2.5"
                      fill="#16323B"
                    />
                  ))
                )}
              </g>

              {/* ===============================================================
                  ILLUSTRATION: Minimalist Girl sitting on cube typing on laptop
                  =============================================================== */}
              <g transform="translate(15, 60)">
                
                {/* Head & Hair Bun */}
                <circle cx="120" cy="20" r="11" fill="#16323B" />
                <path
                  d="M 98,38 C 96,20 114,18 124,30 C 128,40 120,50 114,50 C 105,50 100,44 98,38 Z"
                  fill="#16323B"
                />
                <circle cx="110" cy="38" r="14" fill="#FCEEE8" stroke="#16323B" strokeWidth="1.5" />
                <path d="M 98,32 Q 106,26 112,30" stroke="#16323B" strokeWidth="1.5" fill="none" />
                <path
                  d="M 97,35 L 93,39 L 97,41 L 95,45"
                  fill="none"
                  stroke="#16323B"
                  strokeWidth="1.5"
                  strokeLinecap="round"
                />

                {/* Neck */}
                <path
                  d="M 102,50 L 102,60 L 112,60 L 112,50"
                  fill="#FCEEE8"
                  stroke="#16323B"
                  strokeWidth="1.5"
                />

                {/* Black Top with White Confetti / Specks */}
                <path
                  d="M 102,60 L 74,104 L 126,104 L 118,60 Z"
                  fill="#16323B"
                  stroke="#16323B"
                  strokeWidth="1.5"
                  strokeLinejoin="round"
                />
                <line x1="84" y1="74" x2="88" y2="78" stroke="#FFFFFF" strokeWidth="1.5" strokeLinecap="round" />
                <line x1="98" y1="70" x2="101" y2="74" stroke="#FFFFFF" strokeWidth="1.5" strokeLinecap="round" />
                <line x1="112" y1="80" x2="116" y2="84" stroke="#FFFFFF" strokeWidth="1.5" strokeLinecap="round" />
                <line x1="92" y1="92" x2="96" y2="96" stroke="#FFFFFF" strokeWidth="1.5" strokeLinecap="round" />
                <line x1="106" y1="94" x2="110" y2="98" stroke="#FFFFFF" strokeWidth="1.5" strokeLinecap="round" />

                {/* Arms typing */}
                <path
                  d="M 104,66 Q 82,84 68,102"
                  fill="none"
                  stroke="#16323B"
                  strokeWidth="2.2"
                  strokeLinecap="round"
                />
                <path
                  d="M 110,68 Q 92,88 78,104"
                  fill="none"
                  stroke="#16323B"
                  strokeWidth="2.2"
                  strokeLinecap="round"
                />
                <ellipse cx="64" cy="104" rx="4" ry="2.5" fill="#FCEEE8" stroke="#16323B" strokeWidth="1.2" />

                {/* Laptop on Lap (FinToko Icy Blue #38BFE0) */}
                <polygon
                  points="42,110 74,110 70,100 40,100"
                  fill="#A5E2EE"
                  stroke="#16323B"
                  strokeWidth="1.5"
                  strokeLinejoin="round"
                />
                <polygon
                  points="40,100 36,75 58,75 66,100"
                  fill="#38BFE0"
                  stroke="#16323B"
                  strokeWidth="1.5"
                  strokeLinejoin="round"
                />
                <line x1="42" y1="82" x2="54" y2="82" stroke="#FFFFFF" strokeWidth="1" strokeLinecap="round" />

                {/* White Wide-Leg Trousers (Dangling over left edge of cube) */}
                <path
                  d="M 74,104 C 70,110 64,130 52,160 C 48,172 44,195 42,216 C 42,222 56,224 60,218 C 64,200 68,178 76,165 C 82,165 96,165 102,165 C 108,165 126,104 126,104 Z"
                  fill="#FFFFFF"
                  stroke="#16323B"
                  strokeWidth="1.5"
                  strokeLinejoin="round"
                />
                <path
                  d="M 72,165 L 78,212 C 80,218 92,218 94,212 L 92,165"
                  fill="#FFFFFF"
                  stroke="#16323B"
                  strokeWidth="1.5"
                  strokeLinejoin="round"
                />

                {/* Pointy Black Shoes */}
                <path
                  d="M 40,218 L 34,229 C 36,233 48,231 50,224 L 56,218 Z"
                  fill="#16323B"
                />
                <path
                  d="M 76,214 L 74,225 C 76,229 86,227 88,221 L 90,214 Z"
                  fill="#16323B"
                />
              </g>
            </svg>
          </div>

        </div>
      </main>

      {/* =========================================================================
          FOOTER (Exact layout as Razor: Centered copyright & policy)
          ========================================================================= */}
      <footer className="shrink-0 text-center text-[11px] text-[#577782] py-1.5 sm:py-2 z-20">
        Copyright @FinToko 2026 | Privacy Policy
      </footer>

    </div>
  );
}

export default LoginPage;
