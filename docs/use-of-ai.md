# 🤖 Use of AI

The following outlines the thoughts and approach behind the use of AI.

## Context/Background

When doing skills tests, I have found that some job applications want candidates to use AI, and some do not, but this is not always clear.

Asking for clarity on AI has also yielded mixed results, including vague, open-ended responses or harsh negative ones.

For this reason, I have taken an approach where if AI is not mentioned in the test/assessment scope, either making it a requirement or excluding it, then it becomes a candidates choice, the same way adding a dev dependency would be.

## With or Without AI

In the new world with AI, I assume that businesses want to see that a developer can program and has the skills to accelerate development using AI. However, I have seen even experienced developers struggle to get good results out of AI.

So, a decision to use AI for me normally comes down to the size of the assessment. The larger the scope and time of the assessment, the more likely I believe AI should be used.

Given the size and time frame of this assessment, I decided to use AI.

## AI Tooling

It was decided to come up with an AI-focused agent development workflow. Where possible, AI would be used for planning, coding, and review.

To start, only AI tools with free tiers are used:

- Claude Desktop client with the filesystem MCP (free only)
- Gemini CLI for coding and development (free only)
- Claude Code for coding and development (when available)
- Grok for chat-based problem solving (free only)

I do not have my own Claude Code license, however, a friend of mine sometimes lets me use his private Pro license when he is not using it, and the weekly usage limit has not been reached.

## Comments/Feedback

**Claude Desktop:** Was great for planning exercises (see planning), however, it reached its usage limit very quickly.

**Gemini CLI:** The pro model was decent at coding and problem-solving. However, it reached its usage limit very quickly. Thereafter, the flash model was used. The flash model was found to be slow and only good for simpler coding tasks, and would not do very well in complex coding tasks.

**Grok:** With only a web client, Grok proved very helpful when problem-solving a chat interface was needed, but without local filesystem access, it sometimes needed a lot of copy-pasting back and forth, so usage is limited.

**Claude Code:** Of all the coding changes, Claude Code proved the fastest and best at problem-solving, provided you used the planning feature. However, even with a Pro license, it was easy to reach usage limits, so usage was limited.

## Multi-Agent

The git working tree feature was sometimes used to checkout a maximum of 2 branches at a time. Then two agents would be used at a time on different tasks. For example, Claude Desktop on one branch and Gemini CLI on another, or Gemini for both.

This allowed for switching between multiple tasks while one agent was busy, the output or response from another would be reviewed.

The most this multi-agent approach was extended to was two cli agents, one in each branch, and a Grok chat. More than this, I don't think is feasible as the cognitive load becomes too high.

Multi-agent usage was limited, as blockers and dependencies were common.

##  Planning

Claude Desktop has a project feature that allows for creating a system prompt with supporting files. This feature was used to do a planning exercises where Claude Desktop would create planning markdown files in a local directory. I would then review and iterate on these planning documents before any code was written.

These planning documents filled the space normally used for story and project planning between a team. However, in this case, they were used as prompts for agents.

The planning documents should not be considered perfectly polished or concrete. There was some back and forth to ensure that I reasonably agreed with the AI plan, but there were aspects of the planning docs that could have been removed, or that I did not agree with completely, or were not used.

In a normal team development scenario, requirements would be more concrete but also not as long.

Additionally, as this was a solo coding project and there would be no meetings for alignment on top level requirements, the planning documents were more to serve as a plan and prompt for the AI during development.

The planning docs were not included in the repo submission but can be supplied if requested.

## Approach

AI would be used to create a branch and a PR. The PR was then reviewed and iterated on. Hand coding would often take place when usage limits were reached, or the AI was struggling, or it was faster to refactor myself.

Each PR was not made to be perfect before merging. Quality of code was considered, but I was trying to balance speed and velocity with quality and completeness. Incomplete PRs were also merged so as to unblock work taking place in another branch, as dependency blockers were common at different phases.

Once all AI agents usage limits were reached, only hand coding without any AI was the only option until the limits reset.
