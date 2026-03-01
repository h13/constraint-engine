import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import Anthropic from "@anthropic-ai/sdk";
import { z } from "zod";

const BEAR_API_BASE = process.env.BEAR_API_BASE ?? "http://localhost:8080";

const anthropic = new Anthropic();

const server = new McpServer({
  name: "constraint-engine",
  version: "0.1.0",
});

async function classifyDiff(diff: string): Promise<{ tag: string; confidence: string }> {
  const message = await anthropic.messages.create({
    model: "claude-sonnet-4-5-20250929",
    max_tokens: 100,
    messages: [
      {
        role: "user",
        content: `Classify the following diff between an AI proposal and a human's final decision into exactly one of three categories:
- "factual": AI made a factual error that the human corrected (e.g., wrong API spec, incorrect technical detail)
- "strategic": Human made a business/strategic decision different from AI's suggestion (e.g., client requirements, cost considerations)
- "stylistic": Human adjusted expression, formatting, or terminology (e.g., wording preference, template conformance)

Diff: ${diff}

Respond with ONLY valid JSON: {"tag": "factual"|"strategic"|"stylistic", "confidence": "estimated"}`,
      },
    ],
  });

  const text = message.content[0].type === "text" ? message.content[0].text : "";
  try {
    const parsed = JSON.parse(text) as { tag: string; confidence: string };
    if (["factual", "strategic", "stylistic"].includes(parsed.tag)) {
      return parsed;
    }
  } catch {
    // fallback
  }
  return { tag: "stylistic", confidence: "estimated" };
}

function computeDiff(aiProposal: string, humanFinal: string): string {
  if (aiProposal === humanFinal) {
    return "(no change)";
  }
  return `"${aiProposal}" → "${humanFinal}"`;
}

server.tool(
  "record_checkpoint",
  "Record an AI-human collaboration checkpoint. Detects diff between AI proposal and human final decision, classifies the change, and stores it.",
  {
    aiProposal: z.string().describe("The AI's original proposal"),
    humanFinal: z.string().describe("The human's final decision"),
    taskContext: z.string().describe("Description of the task being worked on"),
    sessionId: z.string().describe("Session identifier for grouping checkpoints"),
  },
  async ({ aiProposal, humanFinal, taskContext, sessionId }) => {
    const diff = computeDiff(aiProposal, humanFinal);
    const classification = await classifyDiff(diff);

    const params = new URLSearchParams({
      sessionId,
      taskContext,
      aiProposal,
      humanFinal,
      diff,
      tag: classification.tag,
      confidence: classification.confidence,
    });

    const response = await fetch(`${BEAR_API_BASE}/checkpoints`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString(),
    });

    if (!response.ok) {
      const body = await response.text();
      return {
        content: [{ type: "text" as const, text: `Error recording checkpoint: ${response.status} ${body}` }],
        isError: true,
      };
    }

    const result = await response.json();
    return {
      content: [
        {
          type: "text" as const,
          text: `Checkpoint recorded successfully.\nClassification: ${classification.tag} (${classification.confidence})\nDiff: ${diff}\nID: ${result.id ?? "unknown"}`,
        },
      ],
    };
  },
);

server.tool(
  "show_pattern",
  "Show the pattern dashboard with classification distribution and trends.",
  {},
  async () => {
    const response = await fetch(`${BEAR_API_BASE}/pattern-dashboard`);
    if (!response.ok) {
      return {
        content: [{ type: "text" as const, text: `Error fetching dashboard: ${response.status}` }],
        isError: true,
      };
    }

    const data = await response.json();
    const summary = data.summary ?? {};
    const distribution = data.tagDistribution ?? [];

    let text = "=== Pattern Dashboard ===\n\n";
    text += `Total checkpoints: ${summary.total ?? 0}\n`;
    text += `  Factual:   ${summary.factual_count ?? 0}\n`;
    text += `  Strategic: ${summary.strategic_count ?? 0}\n`;
    text += `  Stylistic: ${summary.stylistic_count ?? 0}\n`;

    if (distribution.length > 0) {
      text += "\nDistribution:\n";
      for (const item of distribution) {
        text += `  ${item.tag}: ${item.count}\n`;
      }
    }

    return {
      content: [{ type: "text" as const, text }],
    };
  },
);

async function main(): Promise<void> {
  const transport = new StdioServerTransport();
  await server.connect(transport);
}

main().catch(console.error);
