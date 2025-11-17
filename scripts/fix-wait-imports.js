/**
 * Script to fix missing imports for waitForUIUpdate and fastWait
 */

import { readFileSync, writeFileSync } from 'fs';
import { glob } from 'glob';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const testFiles = glob.sync('tests/e2e/**/*.spec.js', {
  cwd: join(__dirname, '..'),
  absolute: true,
});

let filesFixed = 0;

for (const filePath of testFiles) {
  let content = readFileSync(filePath, 'utf8');
  
  // Check if file uses waitForUIUpdate or fastWait but doesn't import them
  const usesWaitForUIUpdate = content.includes('waitForUIUpdate');
  const usesFastWait = content.includes('fastWait');
  
  if (!usesWaitForUIUpdate && !usesFastWait) {
    continue; // Skip files that don't use these functions
  }
  
  // Check if already imported
  const hasImport = content.includes('waitForUIUpdate') && content.includes('from') && 
                    content.includes('./helpers/wait.js');
  const hasFastWaitImport = content.includes('fastWait') && content.includes('from') && 
                            content.includes('./helpers/wait.js');
  
  if ((usesWaitForUIUpdate && hasImport) && (usesFastWait && hasFastWaitImport)) {
    continue; // Already imported
  }
  
  // Find existing wait.js import
  const waitImportMatch = content.match(/import\s+\{([^}]+)\}\s+from\s+['"]\.\/helpers\/wait\.js['"]/);
  
  if (waitImportMatch) {
    // Update existing import
    let imports = waitImportMatch[1].trim();
    let needsUpdate = false;
    
    if (usesWaitForUIUpdate && !imports.includes('waitForUIUpdate')) {
      imports += ', waitForUIUpdate';
      needsUpdate = true;
    }
    
    if (usesFastWait && !imports.includes('fastWait')) {
      imports += ', fastWait';
      needsUpdate = true;
    }
    
    if (needsUpdate) {
      content = content.replace(
        /import\s+\{([^}]+)\}\s+from\s+['"]\.\/helpers\/wait\.js['"]/,
        `import { ${imports} } from './helpers/wait.js'`
      );
      filesFixed++;
    }
  } else {
    // Add new import after other helper imports
    const helperImportMatch = content.match(/(import\s+.*from\s+['"]\.\/helpers\/.*['"];?\n)/);
    if (helperImportMatch) {
      // Add after first helper import
      let newImport = `import { `;
      if (usesWaitForUIUpdate) newImport += 'waitForUIUpdate';
      if (usesWaitForUIUpdate && usesFastWait) newImport += ', ';
      if (usesFastWait) newImport += 'fastWait';
      newImport += ` } from './helpers/wait.js';\n`;
      
      content = content.replace(
        /(import\s+.*from\s+['"]\.\/helpers\/.*['"];?\n)/,
        `$1${newImport}`
      );
      filesFixed++;
    } else {
      // Add after @playwright/test import
      const playwrightImportMatch = content.match(/(import\s+.*from\s+['"]@playwright\/test['"];?\n)/);
      if (playwrightImportMatch) {
        let newImport = `import { `;
        if (usesWaitForUIUpdate) newImport += 'waitForUIUpdate';
        if (usesWaitForUIUpdate && usesFastWait) newImport += ', ';
        if (usesFastWait) newImport += 'fastWait';
        newImport += ` } from './helpers/wait.js';\n`;
        
        content = content.replace(
          /(import\s+.*from\s+['"]@playwright\/test['"];?\n)/,
          `$1${newImport}`
        );
        filesFixed++;
      }
    }
  }
  
  if (filesFixed > 0) {
    writeFileSync(filePath, content, 'utf8');
  }
}

console.log(`✅ Fixed imports in ${filesFixed} files`);

