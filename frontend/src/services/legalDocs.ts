export const effective = 'October 16, 2026';

export interface Section { heading: string; paragraphs?: string[]; items?: string[] }
export interface Doc { title: string; summary: string; sections: Section[] }

export const terms: Doc = {
  title: 'Terms of Service',
  summary: 'The rules for using the PinaCall University Portal for admissions, enrollment, clearance, grades and document requests.',
  sections: [
    { heading: 'Acceptance of these terms', paragraphs: [
      'PinaCall is the online academic services portal of Pamantasan ng Cabuyao (University of Cabuyao). By creating an account, signing in or otherwise using the portal you agree to these Terms of Service and to our Privacy Policy.',
      'If you do not agree, please do not use the portal and contact the Registrar or ICT Service Desk for assistance through other channels.'] },
    { heading: 'Who may use the portal', items: [
      'Applicants, enrolled and returning students, faculty, department heads, registrar, cashier and administrative staff with an account issued or approved by the University.',
      'Your role determines which services and records you can see. You may not attempt to access features or data outside your role.',
      'Student self-registration creates an application only; enrollment is subject to the University’s evaluation and approval.'] },
    { heading: 'Your account', items: [
      'Provide accurate, complete and current information, including your legal name and the documents you submit.',
      'Keep your password confidential. You are responsible for activity under your account until you report a suspected compromise.',
      'Sign out on shared devices. Sessions are cleared when you sign out or close the browser tab.',
      'The University may suspend or deactivate accounts that are inactive, misused, or no longer authorised.'] },
    { heading: 'Acceptable use', paragraphs: ['You agree not to:'], items: [
      'submit false, forged or misleading information, credentials, payments or document requests;',
      'access, alter or attempt to view another person’s records, or share your access with others;',
      'probe, scan, overload, reverse-engineer or otherwise interfere with the portal or its security;',
      'upload malware or content that is unlawful, harassing or infringes the rights of others;',
      'use automated tools to scrape or bulk-submit data without written permission.'] },
    { heading: 'Academic records, clearance and payments', items: [
      'Grades are shown to students only after they are released by the University. Posted grades and statuses reflect the official records at the time of viewing; the Registrar’s records prevail in any discrepancy.',
      'Clearance is a multi-office process. Completion in the portal does not replace any office requirement that must be settled in person.',
      'Fees for documents or services are assessed by the Cashier. Payment status in the portal may take time to reflect and is confirmed by an official receipt.',
      'Document requests are processed in the order and timelines set by the issuing office.'] },
    { heading: 'Intellectual property', paragraphs: [
      'The portal software, design and content, and the Pamantasan ng Cabuyao name, seal and marks, belong to their respective owners and are used with permission. You may use the portal for your own academic and administrative purposes, but may not copy, redistribute or create derivative works from it without permission.'] },
    { heading: 'Availability and changes', paragraphs: [
      'We aim to keep the portal available but do not guarantee uninterrupted service. Maintenance, network problems or events outside our control may cause delays. Features may change or be withdrawn, and we may update these terms; the effective date above shows the latest version. Continued use after an update means you accept it.'] },
    { heading: 'Disclaimer and limitation of liability', paragraphs: [
      'The portal is provided “as is” to the extent allowed by law. The University and the portal’s developers are not liable for indirect or consequential loss arising from use of, or inability to use, the portal, except where liability cannot be excluded by Philippine law.'] },
    { heading: 'Enforcement and governing law', paragraphs: [
      'Violations may lead to suspension of access, referral under the University’s student or employee disciplinary rules, and, where applicable, legal action. These terms are governed by the laws of the Republic of the Philippines. Disputes shall be raised first with the Registrar or the University administration.'] },
    { heading: 'Contact', paragraphs: ['For questions, corrections or account problems, contact the Registrar or the ICT Service Desk at support@pinacall.edu during office hours.'] },
  ],
};

export const privacy: Doc = {
  title: 'Privacy Policy',
  summary: 'What personal data the PinaCall portal collects, why, how it is protected, and the rights you have under the Philippine Data Privacy Act of 2012.',
  sections: [
    { heading: 'Who we are', paragraphs: [
      'PinaCall is operated for Pamantasan ng Cabuyao (University of Cabuyao), Banay-Banay, Cabuyao City, Laguna, Philippines, which acts as the personal information controller. This policy follows Republic Act No. 10173, the Data Privacy Act of 2012, its implementing rules, and the issuances of the National Privacy Commission.'] },
    { heading: 'Information we collect', items: [
      'Account details: full name, email address, role, department and password (stored only in hashed form).',
      'Student and applicant details: student category, program, year level, student number, enrollment application and the credentials you submit.',
      'Academic records: course and section enrollment, ratings and grades, grade release status, and clearance status from each office.',
      'Service records: document requests, payment and processing status, and related notes by University staff.',
      'Technical data: an access token kept for your browser session, and your light/dark display preference stored on your own device.'] },
    { heading: 'Why we use it', items: [
      'To verify your identity and give you access to the services your role allows.',
      'To process admissions, enrollment, clearance, grading, and document requests.',
      'To communicate service updates and respond to support requests.',
      'To keep the portal secure, prevent misuse, and meet legal and reporting obligations of the University.'] },
    { heading: 'Legal basis', paragraphs: [
      'We process personal information to fulfil the University’s educational and administrative functions, to comply with legal obligations, and, where required, with your consent. Sensitive personal information is processed only as allowed under Sections 12 and 13 of the Data Privacy Act.'] },
    { heading: 'Who can see your data', items: [
      'You can see your own records. University offices see only what they need for their function: the Registrar for admissions and documents, the Cashier for payments, department heads and deans for academic matters, faculty for their own class rosters and gradebooks, and administrators for system oversight.',
      'We do not sell personal data. Data is shared outside the University only when required by law or by a government agency, or with service providers who host the portal and are bound to protect it.',
      'The developers’ public GitHub profiles listed in the footer are links to their own accounts; no portal data is shared with GitHub by following them, although your browser will load their profile pictures from GitHub.'] },
    { heading: 'Storage, security and retention', items: [
      'Data is protected with access controls by role, hashed passwords, encrypted connections and expiring session tokens.',
      'Academic and enrollment records are kept for as long as the University needs them under its records retention rules; other data is kept no longer than necessary for the purpose collected.',
      'No system is completely secure. If a breach affects your data, we will notify you and the National Privacy Commission as required by law.'] },
    { heading: 'Cookies and local storage', paragraphs: [
      'The portal does not use advertising or tracking cookies. It keeps a session token in your browser’s session storage until you sign out or close the tab, and saves your theme choice (light or dark) in local storage so it is remembered.'] },
    { heading: 'Your rights', paragraphs: ['As a data subject you have the right to:'], items: [
      'be informed about how your data is processed;',
      'access a copy of your personal data;',
      'object to processing or withdraw consent where processing is based on it;',
      'request correction of inaccurate or incomplete data;',
      'request blocking, removal or destruction where the data is unlawfully obtained or no longer needed, subject to records the University must keep;',
      'be indemnified for damages from inaccurate, unlawfully obtained or unauthorised use of your data;',
      'file a complaint with the National Privacy Commission.'] },
    { heading: 'Children and minors', paragraphs: [
      'The portal is meant for university applicants, students and personnel. If a minor applies, processing is carried out only as needed for admission and with any parental or guardian consent the University requires.'] },
    { heading: 'Changes and contact', paragraphs: [
      'We may update this policy and will change the effective date above when we do. To exercise your rights or raise a privacy concern, contact the University’s Data Protection Officer through the Registrar or the ICT Service Desk at support@pinacall.edu.'] },
  ],
};
