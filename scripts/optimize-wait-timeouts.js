/**
 * Script to optimize waitForTimeout usage in E2E tests
 * 
 * Replaces waitForTimeout with optimized wait strategies:
 * - waitForTimeout(500) -> waitForUIUpdate(page)
 * - waitForTimeout(100-300) -> fastWait(page, ms)
 * - waitForTimeout(1000+) -> waitForUIUpdate(page) or waitForDebounce(page)
 * 
 * This script should be run carefully and tests should be verified after changes.
 */

import { readFileSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { glob } from 'glob';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const testFiles = glob.sync('tests/e2e/**/*.spec.js', {
  cwd: join(__dirname, '..'),
  absolute: true,
});

let totalReplacements = 0;
let filesModified = 0;

for (const filePath of testFiles) {
  let content = readFileSync(filePath, 'utf8');
  let modified = false;
  let fileReplacements = 0;

  // Replace patterns:
  // 1. waitForTimeout(500) -> waitForUIUpdate(page)
  // 2. waitForTimeout(300) -> fastWait(page, 300)
  // 3. waitForTimeout(100-200) -> fastWait(page, ms)
  // 4. waitForTimeout(1000+) -> waitForUIUpdate(page)

  // Pattern 1: waitForTimeout(500) for debounce/search
  const debouncePattern = /await\s+page\.waitForTimeout\(500\)/g;
  if (debouncePattern.test(content)) {
    content = content.replace(debouncePattern, "await waitForUIUpdate(page)");
    modified = true;
    fileReplacements += (content.match(/waitForUIUpdate\(page\)/g) || []).length - 
                        (content.match(/import.*waitForUIUpdate/g) || []).length;
  }

  // Pattern 2: waitForTimeout(300) or waitForTimeout(100-400) for short delays
  const shortWaitPattern = /await\s+page\.waitForTimeout\((\d{1,3})\)/g;
  content = content.replace(shortWaitPattern, (match, ms) => {
    const msNum = parseInt(ms, 10);
    if (msNum <= 400) {
      modified = true;
      fileReplacements++;
      return `await fastWait(page, ${ms})`;
    }
    return match;
  });

  // Pattern 3: waitForTimeout(1000+) for longer waits
  const longWaitPattern = /await\s+page\.waitForTimeout\((\d{4,})\)/g;
  content = content.replace(longWaitPattern, (match, ms) => {
    modified = true;
    fileReplacements++;
    return `await waitForUIUpdate(page)`;
  });

  // Add imports if needed
  if (modified && !content.includes('waitForUIUpdate') && !content.includes('fastWait')) {
    // Find the import statement location
    const importMatch = content.match(/import\s+.*from\s+['"]\.\/helpers\/wait\.js['"]/);
    if (importMatch) {
      // Update existing import
      content = content.replace(
        /import\s+\{([^}]+)\}\s+from\s+['"]\.\/helpers\/wait\.js['"]/,
        (match, imports) => {
          let newImports = imports.trim();
          if (!newImports.includes('waitForUIUpdate')) {
            newImports += ', waitForUIUpdate';
          }
          if (!newImports.includes('fastWait')) {
            newImports += ', fastWait';
          }
          return `import { ${newImports} } from './helpers/wait.js'`;
        }
      );
    } else {
      // Add new import at the top after other imports
      const importPattern = /(import\s+.*from\s+['"]\.\/helpers\/.*['"];?\n)/;
      const firstHelperImport = content.match(importPattern);
      if (firstHelperImport) {
        content = content.replace(
          importPattern,
          `$1import { waitForUIUpdate, fastWait } from './helpers/wait.js';\n`
        );
      } else {
        // Add at the beginning after describe/test imports
        const testImportMatch = content.match(/(import\s+.*from\s+['"]@playwright\/test['"];?\n)/);
        if (testImportMatch) {
          content = content.replace(
            /(import\s+.*from\s+['"]@playwright\/test['"];?\n)/,
            `$1import { waitForUIUpdate, fastWait } from './helpers/wait.js';\n`
          );
        }
      }
    }
  }

  if (modified) {
    writeFileSync(filePath, content, 'utf8');
    totalReplacements += fileReplacements;
    filesModified++;
    console.log(`✓ Optimized ${filePath}: ${fileReplacements} replacements`);
  }
}

console.log(`\n✅ Optimization complete!`);
console.log(`   Files modified: ${filesModified}`);
console.log(`   Total replacements: ${totalReplacements}`);
console.log(`\n⚠️  Please review the changes and run tests to verify.`);

